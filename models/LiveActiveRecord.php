<?php

namespace app\models;

use Yii;

/**
 * This is the model for live fields in ActiveRecord's
 */
class LiveActiveRecord extends TranslatedActiveRecord
{

    /**
     * @var array An array holding the values of the record before changing
     */
    private $presaveAttributes;

    /**
     * @inheritdoc
     */
    public function init()
    {

        $instance = $this;
        $this->on(self::EVENT_BEFORE_UPDATE, function($instance){
            $this->presaveAttributes = $this->getOldAttributes();
        });

        // For each live field, such an event needs to be fired
        foreach ($this->liveFields as $field => $config) {

            // if the field is given as string without a config, take the default config
            if (is_int($field)) {
                $field = $config;
                $config = [];
            }
            $this->on(self::EVENT_AFTER_UPDATE, [$this, 'updateEvent'], [$field, $config]);
        }

        parent::init();
    }

    /**
     * The default configuration for a live field if no other is given.
     * These field are merged with the provided array in a way that the
     * values of the given array overwrite the ones below.
     * 
     * @return array The default configuration
     */
    private function defaultConfig() {
        return [
            'event' => function ($field, $model) {
                // default value is table/id
                return $model->tableName() . '/' . $model->id;
            },
            'priority' => 0,
            'data' => function ($field, $model) {
                // default value is [key => value]
                return [ $field => $this->{$field} ];
            },
            'category' => function ($field, $model) {
                // check whether the field is a translated field
                return in_array($field, $model->translatedFields) ? $model->tableName() : null;
            },
        ];
    }

    /**
     * A list of attributes whose modification triggers the event
     * 
     * @param string $field The live field
     * @return array List of properties
     */
    private function triggerAttributes($field) {
        return in_array($field, $this->translatedFields) ? [$field . '_id', $field . '_data'] : [$field];
    }

    /**
     * A list of database fields that are live
     * 
     * TODO: explanantion
     * @return array
     */
    public function getLiveFields()
    {
        return [];
    }

    /**
     * Checks if attributes have changed
     * 
     * @param array $attributes - a list of attributes to check
     * @return bool
     */
    public function attributesChanged($attributes)
    {
        foreach($attributes as $attribute){
            if (array_key_exists($attribute, $this->presaveAttributes) && array_key_exists($attribute, $this->attributes)) {
                if ($this->presaveAttributes[$attribute] != $this->attributes[$attribute]){
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * The event to generate the EventItem's based on the given config
     *
     * @param yii\base\Event $event the event
     * @return void
     */
    public function updateEvent($event)
    {

        if ($event->data !== null) {
            $field = $event->data[0];
            $config = $event->data[1];

            if ($this->attributesChanged($this->triggerAttributes($field))) {
                $realConfig = array_merge($this->defaultConfig(), $config);

                // evaluate the anonymous functions
                foreach ($realConfig as $key => $value) {
                    if (is_callable($value)) {
                        $realConfig[$key] = $value($field, $this);
                    }
                }

                //var_dump($realConfig);

                $eventItem = new EventItem($realConfig);
                $eventItem->generate();
            }
        }
    }
}