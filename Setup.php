<?php
/**
 * Badges xF2 addon by CMTV
 * Enjoy!
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
    // INSTALLATION
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

    //
    // UNINSTALLATION
    //

    /* Removing tables and addon columns */
    public function uninstallStep1()
    {
        $this->schemaManager()->dropTable(C::_table('badge'));
        $this->schemaManager()->dropTable(C::_table('badge_category'));
        $this->schemaManager()->dropTable(C::_table('reason'));

        $this->schemaManager()->alterTable('xf_user', function (Alter $table) {
            $table->dropColumns(C::_column('badge_count'));
        });
    }

    /* Removing phrases */
    public function uninstallStep2()
    {
        $phrases = $this->app->finder('XF:Phrase')->where(['title', 'LIKE', 'CMTV_Badges_%'])->fetch();

        foreach ($phrases as $phrase) {
            $phrase->delete(false);
        }
    }

    //
    // UPGRADE
    //

}