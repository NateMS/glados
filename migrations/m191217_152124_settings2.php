<?php

use yii\db\Migration;
use app\models\Setting;

/**
 * Class m191217_152124_settings2
 */
class m191217_152124_settings2 extends Migration
{
    public $settingsTable = 'setting';

    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {

        $this->addColumn($this->settingsTable, 'description_data', $this->string(1024)->defaultValue(null));
        $this->addColumn($this->settingsTable, 'description_id', $this->integer(11)->notNull());
        $this->addColumn($this->settingsTable, 'name_data', $this->string(1024)->defaultValue(null));
        $this->addColumn($this->settingsTable, 'name_id', $this->integer(11)->notNull());

        // edit loginHint
        $loginHint = Setting::find()->where(['or',
            ['key' => 'Login hint'],
            ['key' => 'loginHint'],
        ])->one();
        $loginHint->key = 'loginHint';
        $loginHint->name = yiit('setting', 'Login hint');
        $loginHint->description = yiit('setting', 'A hint that is show in the login form for users attempting to login.');
        $loginHint->save();

        // create tokenLength
        $tokenLength = new Setting([
            'key' => 'tokenLength',
            'name' => yiit('setting', 'Token length'),
            'type' => 'integer',
            'default_value' => 10,
            'description' => yiit('setting', 'The length of the token, when generated in number of characters.'),
        ]);
        $tokenLength->save(false);

        // create minDaemons
        $minDaemons = new Setting([
            'key' => 'minDaemons',
            'name' => yiit('setting', 'Minimal daemons'),
            'type' => 'integer',
            'default_value' => 3,
            'description' => yiit('setting', 'The minimal number of running daemons, that are started if at least one daemon is running.'),
        ]);
        $minDaemons->save(false);

        // create maxDaemons
        $maxDaemons = new Setting([
            'key' => 'maxDaemons',
            'name' => yiit('setting', 'Maximal daemons'),
            'type' => 'integer',
            'default_value' => 10,
            'description' => yiit('setting', 'The maximal number of running daemons, that are started by each other.'),
        ]);
        $maxDaemons->save(false);

        // create upperBound
        $upperBound = new Setting([
            'key' => 'upperBound',
            'name' => yiit('setting', 'Upper bound'),
            'type' => 'integer',
            'default_value' => 80,
            'description' => yiit('setting', 'The upper threshold in percent of the average load of all running daemons, such that a new daemons is started.'),
        ]);
        $upperBound->save(false);

        // create lowerBound
        $lowerBound = new Setting([
            'key' => 'lowerBound',
            'name' => yiit('setting', 'Lower bound'),
            'type' => 'integer',
            'default_value' => 20,
            'description' => yiit('setting', 'The lower threshold in percent of the average load of all running daemons, such that one of them is stopped.'),
        ]);
        $lowerBound->save(false);

        // create abandonTicket
        $abandonTicket = new Setting([
            'key' => 'abandonTicket',
            'name' => yiit('setting', 'Ticket abandon time'),
            'type' => 'integer',
            'default_value' => 10800,
            'description' => yiit('setting', 'The time in seconds after the last successful backup, after which the ticket is left abandoned.'),
        ]);
        $abandonTicket->save(false);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        Setting::deleteAll(['key' => 'Token length']);
        Setting::deleteAll(['key' => 'tokenLength']);
        Setting::deleteAll(['key' => 'minDaemons']);
        Setting::deleteAll(['key' => 'maxDaemons']);
        Setting::deleteAll(['key' => 'upperBound']);
        Setting::deleteAll(['key' => 'lowerBound']);
        Setting::deleteAll(['key' => 'abandonTicket']);

        $this->dropColumn($this->settingsTable, 'description_data');
        $this->dropColumn($this->settingsTable, 'description_id');
        $this->dropColumn($this->settingsTable, 'name_data');
        $this->dropColumn($this->settingsTable, 'name_id');
    }
}
