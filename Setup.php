<?php
/**
 * [VersoBit] Badges
 */

namespace CMTV\Badges;

use CMTV\Badges\Constants as C;
use XF\AddOn\AbstractSetup;
use XF\AddOn\StepRunnerInstallTrait;
use XF\AddOn\StepRunnerUninstallTrait;
use XF\AddOn\StepRunnerUpgradeTrait;
use XF\Db\Schema\Alter;
use XF\Db\Schema\Create;

class Setup extends AbstractSetup
{
    use StepRunnerInstallTrait;
    use StepRunnerUpgradeTrait;
    use StepRunnerUninstallTrait;

    //
    // INSTALL
    //

    /* Table for badges */
    public function installStep1()
    {
        $this->schemaManager()->createTable(C::_table('badge'), function (Create $table) {
            $table->addColumn('badge_id', 'int')->autoIncrement();
            $table->addColumn('user_criteria', 'mediumblob');
            $table->addColumn('badge_category_id', 'int')->setDefault(0);
            $table->addColumn('icon_type', 'enum')->values(['fa', 'image']);
            $table->addColumn('fa_icon', 'varchar', 50)->setDefault('');
            $table->addColumn('image_url', 'varchar', 200)->setDefault('');
            $table->addColumn('class', 'varchar', 50)->setDefault('');
            $table->addColumn('display_order', 'int')->setDefault(10);
        });
    }

    /* Table for badge categories */
    public function installStep2()
    {
        $this->schemaManager()->createTable(C::_table('badge_category'), function (Create $table) {
            $table->addColumn('badge_category_id', 'int')->autoIncrement();
            $table->addColumn('icon_type', 'enum')->values(['', 'fa', 'image'])->setDefault('');
            $table->addColumn('fa_icon', 'varchar', 50)->setDefault('');
            $table->addColumn('image_url', 'varchar', 200)->setDefault('');
            $table->addColumn('class', 'varchar', 50)->setDefault('');
            $table->addColumn('display_order', 'int')->setDefault(10);
        });
    }

    /* Table for storing data about user badges */
    public function installStep3()
    {
        $this->schemaManager()->createTable(C::_table('user_badge'), function (Create $table) {
            $table->addColumn('user_id', 'int');
            $table->addColumn('badge_id', 'int');
            $table->addColumn('award_date', 'int');
            $table->addColumn('reason', 'text');
            $table->addColumn('featured', 'tinyint', 3)->setDefault(0);
            $table->addPrimaryKey(['badge_id', 'user_id']);
        });
    }

    public function installStep4()
    {
        $this->schemaManager()->alterTable('xf_user', function (Alter $table) {
            $table->addColumn(C::_column('badge_count'), 'int')->setDefault(0);
        });
    }

    /* Setting default values for permissions */
    public function installStep5()
    {
        $registeredPermissions = [
            'manageFeatured'
        ];

        $moderatorPermissions = [
            'award',
            'takeAway'
        ];

        foreach ($registeredPermissions as $permission) {
            $this->applyGlobalPermission(
                C::_(),
                $permission,
                'forum',
                'editOwnPost'
            );
        }

        foreach ($moderatorPermissions as $permission) {
            $this->applyGlobalPermission(
                C::_(),
                $permission,
                'general',
                'editBasicProfile'
            );
        }

        $this->applyGlobalPermissionInt(
            C::_(),
            'featuredNumber',
            4,
            'forum',
            'editOwnPost'
        );
    }
    /* Add Custom User Field: Preferences (Email Opt-Out) */
    public function installStep6()
    {
        if (!\XF::em()->find('XF:UserField', 'CMTV_Badges_Email_OptOut')) {
            $field = \XF::em()->create('XF:UserField');
            $title = $field->getMasterPhrase(true);
            $title->phrase_text = 'Email Opt-Out';
            $field->addCascadedSave($title);

            $description = $field->getMasterPhrase(false);
            $description->phrase_text = 'Enable to disable email notifications about badges';
            $field->addCascadedSave($description);

            $field->field_id = 'CMTV_Badges_Email_OptOut';
            $field->display_group = 'preferences';
            $field->display_order = 1;
            $field->field_type = 'checkbox';
            $field->field_choices = ['1' => 'Opt-out of emails about badges'];
            $field->match_type = 'none';
            $field->match_params = [];
            $field->max_length = 0;
            $field->required = 0;
            $field->show_registration = 0;
            $field->user_editable = 'yes';
            $field->viewable_profile = 0;
            $field->viewable_message = 0;
            $field->moderator_editable = 0;

            $field->save();
        }
    }

    //
    // UNINSTALL
    //

    /* Removing tables and addon columns */
    public function uninstallStep1()
    {
        $this->schemaManager()->dropTable(C::_table('badge'));
        $this->schemaManager()->dropTable(C::_table('badge_category'));
        $this->schemaManager()->dropTable(C::_table('user_badge'));

        $this->schemaManager()->alterTable('xf_user', function (Alter $table) {
            $table->dropColumns(C::_column('badge_count'));
        });
    }

    /* Removing phrases */
    public function uninstallStep2()
    {
        $phrases = $this->app->finder('XF:Phrase')->where(['title', 'LIKE', '%CMTV_Badge%'])->fetch();

        foreach ($phrases as $phrase) {
            $phrase->delete(false);
        }
    }

    /* Removing custom user fields and associated preferences */
    public function uninstallStep3()
    {
        $userFields = $this->app->finder('XF:UserField')->where(['field_id', 'LIKE', '%CMTV_Badge%'])->fetch();
        foreach ($userFields as $userField) {
            $userField->delete(false);
            $userFieldValues = $this->app->finder('XF:UserFieldValue')->where(['field_id', 'LIKE', '%CMTV_Badge%'])->fetch();
            foreach ($userFieldValues as $userFieldValue){
                $userFieldValue->delete(false);
            }
        }
    }

    //
    // UPGRADE
    //

    /* Upgrading to 1000770 (1.0.7|XF2.1)
       Add Custom User Field: Preferences (Email Opt-Out) */
    public function upgrade1000770Step1()
    {
        $this->installStep6();
    }

    /* Upgrading to 2000070 (2.0.0|XF2.2)
       Remove vbBadges from database and replace with CMTV_Badges (Standards Cleanup) */
    public function upgrade2000070Step1()
    {
        $phrases = $this->app->finder('XF:Phrase')->where(['title', 'LIKE', '%vbBadges%'])->fetch();

        foreach ($phrases as $phrase) {
            $phrase->delete(false);
        }
    }
    public function upgrade2000070Step2()
    {
        //Delete vbBadges* from UserField
        $userFields = $this->app->finder('XF:UserField')->where(['field_id', 'LIKE', '%vbBadges%'])->fetch();
        foreach ($userFields as $userField) {
            $userField->delete(false);
        }
    }
    public function upgrade2000070Step3()
    {
        //Install New UserField
        $this->installStep6();
    }
    public function upgrade2000070Step4()
    {
        //Change field_id on entries in UserFieldValue (vBbadgesEmailOptOut > CMTV_Badges_Email_OptOut)
        //TODO: Prevent breaking existing settings for the email opt-out (vBbadgesEmailOptOut > CMTV_Badges_Email_OptOut)
        //$userFieldValues = $this->app->finder('XF:UserFieldValue')->where(['field_id', 'LIKE', '%vbBadges%'])->fetch();
        //foreach ($userFieldValues as $userFieldValue){
        //    $userFieldValue->delete(false);
        //}
    }

}