<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * This is the base model class.
 *
 * @property integer $id
 * @property string $name
 * @property string $subject
 * @property boolean $grp_netdev
 * @property boolean $allow_sudo
 * @property boolean $allow_mount
 * @property boolean $firewall_off
 * @property boolean $screenshots
 * @property string $file
 * @property integer $user_id
 * @property string $file_list
 *
 * @property User $user
 * 
 * @property Ticket[] $tickets
 * @property integer ticketCount
 * @property integer openTicketCount
 * @property integer runningTicketCount
 * @property integer closedTicketCount
 */
class Base extends \yii\db\ActiveRecord
{

    /**
     * @var bool disables the permissions check in [[selectList]]
     */
    public $noPermissionCheck = false;

    /**
     * List of tables that are able to join
     *
     * @return array an array with join tables in format [ "table1 alias1", "table2 alias2" ]
     */
    public function joinTables() {
        return [];
    }

    /**
     * Lists an attribute
     * @param string attr attribute to list
     * @param string q query
     * @param int page
     * @param int per_page
     * @param int id which attribute should be the id
     * @param bool showQuery whether the query itself should be shown in the 
     *                  output list.
     * @param string the attribute to order by
     *
     * @return array
     */
    public function selectList($attr, $q, $page = 1, $per_page = 10, $id = null, $showQuery = true, $orderBy = null)
    {

        $id = is_null($id) ? $attr : $id;

        $query = $this->find();

        if ($this->hasMethod('getTranslatedFields') && in_array($attr, $this->getTranslatedFields())) {
            //nothing
        } else {
            $query->addSelect([$id . ' as xxxidxxx', $attr . ' AS xxxattrxxx']);
                //->distinct();
            $id = 'xxxidxxx';
            $attr = 'xxxattrxxx';
        }


        $query->joinWith($this->joinTables());

        if (!is_null($q) && $q != '') {
            $query->having(['like', $attr, $q]);
        }

        is_null($orderBy) ? $query->orderBy($attr) : $query->orderBy($orderBy);

        $query->groupBy($attr); // distincts even a calculated field

        if ($this->tableName() != "user") {
            if (!$this->noPermissionCheck) {
                Yii::$app->user->can($this->tableName() . '/index/all') ?: $query->own();
            }
        }

        $out = ['results' => []];
        if ($showQuery === true && $page == 1 && $q != null) {
            $out = ['results' => [
                0 => ['id' => $q, 'text' => $q == null ? $q : \Yii::t('form', '<i>Search for... </i><b>{query}</b>', ['query' => $q])]
            ]];
            $per_page -= 1;
        }

        $command = $query->limit($per_page)->offset(($page-1)*$per_page)->createCommand();
        $data = $command->queryAll();
        foreach ($data as $key => $value) {
            $out['results'][] = [
                'id' => $value[$id],
                // highlight the matching part
                'text' => $q == null ?
                    $value[$attr] :
                    preg_replace('/'.$q.'/i', '<b>$0</b>', $value[$attr])
            ];
        }
        return $out;
    }

}
