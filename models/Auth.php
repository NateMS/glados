<?php

namespace app\models;

use Yii;
use yii\base\Model;
use yii\base\ErrorException;
use yii\helpers\FileHelper;
use yii\web\UnprocessableEntityHttpException;
use app\models\AuthInterface;

/**
 * This is the model class for the Auth class.
 *
 * @property app\components\Ad|null $obj The instantiated configuration object of the authentication type. This property is read-only.
 * @property array $configArray @todo: remove
 */
class Auth extends Model implements AuthInterface
{

    const SCENARIO_CREATE = 'create';
    const SCENARIO_MIGRATE = 'migrate';
    const SCENARIO_AUTH_TEST = 'auth_test';

    /**
     * @const string Scenario to query for usernames
     */
    const SCENARIO_QUERY_USERS = 'query_users';

    /* authentication type constants */
    const AUTH_LOCAL = 0;
    const AUTH_LDAP = 1;
    const AUTH_ACTIVE_DIRECTORY = 2;
    const AUTH_OPENLDAP = 3;
    
    //public $configPath = __DIR__ . '/../config';
    const PATH = __DIR__ . '/../config';

    public $id;

    /**
     * @var string The pattern to test the given login credentials against.
     */
    public $loginScheme = '{username}';

    /**
     * @var string The class of the current authentication type.
     */
    public $class = 'app\models\Auth';

    /**
     * @var string The type of the current authentication.
     */
    public $type;

    /**
     * @var string The authentication type in human readable form
     */
    public $typeName = 'Unknown Authentication Method';

    /**
     * @var string The view for the current authentication type.
     */
    public $view = 'view';

    /**
     * @var string The edit form view for the current authentication type.
     */
    public $form = '_form';

    /**
     * @var string A name for the current authentication type.
     */
    public $name;

    /**
     * @var string A description for the current authentication type.
     */
    public $description;

    /**
     * @var int The order in which the authentication process should be processed.
     */
    public $order;

    /**
     * @var array Array of authentications methods.
     */
    public $methods;

    /**
     * @var array An array of debug messages to test the connection.
     */
    public $debug = [];

    /**
     * @var string A string holding the last error message.
     */
    public $error = null;

    /**
     * @var string A string holding the last success message.
     */
    public $success = null;

    /**
     * @var string A search pattern for the username of local users to migrate
     */
    public $migrateSearchPattern = '';

    /**
     * @var array Array of LDAP users for the select list of the migration form.
     *
     * $this->migrateUsers = [
     *      '{migrateUsersKeyScheme}' => '{migrateUsersValueScheme}',
     *      ...
     *      '{migrateUsersKeyScheme}' => '{migrateUsersValueScheme}',
     * ];
     *
     */
    public $migrateUsers = [];

    /**
     * @var string Schemes for the key and value of the [[migrateUsers]] array
     */
    public $migrateUsersKeyScheme = '{id} -> {identifier}';
    public $migrateUsersValueScheme = '{username} (id={id})';

    /**
     * @var string The migration form view for the current authentication type.
     */
    public $migrationForm = '_form_migrate';

    /**
     * @var string Id of the authentication method users are migrated from.
     */
    public $migrateFrom;

    private $_obj = null; // @TODO: remove
    private $_configArray = null; // @TODO: remove

    /**
     * @return array the validation rules.
     */
    public function rules()
    {
        return [
            [['name', 'class', 'order', 'loginScheme'], 'required'],
            [['name'], 'string', 'max' => 10],
            [['class'], 'required', 'on' => self::SCENARIO_CREATE],
            ['class', 'in', 'range' => array_keys($this->authList)],
            ['order', 'in',
                'not' => true,
                'range' => $this->orderRange(),
                'message' => Yii::t('auth', 'This number is already assigned to another authentication method.'),
            ],
            [['order'], 'integer', 'min' => 1, 'max' => 9999],
            ['migrateSearchPattern', 'queryUsers', 'skipOnEmpty' => false, 'skipOnError' => false, 'on' => self::SCENARIO_QUERY_USERS],
        ];
    }

    /**
     * @return array Array of order numbers which are already assigned to other
     * authentication methods.
     */
    public function orderRange()
    {
        $keys = array_keys($this->fileConfig);
        $values = array_column($this->fileConfig, 'order');
        $a = array_combine($keys, $values);
        if (array_key_exists($this->id, $a)) {
            unset($a[$this->id]);
        }
        return $a;
    }

    /**
     * @inheritdoc
     */
    public function scenarios()
    {
        return [
            self::SCENARIO_DEFAULT => ['class', 'name', 'description', 'order'],
            self::SCENARIO_CREATE => ['class'],
            self::SCENARIO_MIGRATE => ['class'],
            self::SCENARIO_QUERY_USERS => ['migrateSearchPattern'],
        ];
    }

    /**
     * @return array customized attribute labels
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('auth', 'ID'),
            'order' => Yii::t('auth', 'Order'),
            'class' => Yii::t('auth', 'Method'),
            'name' => Yii::t('auth', 'Name'),
            'typeName' => Yii::t('auth', 'Type'),
            'description' => Yii::t('auth', 'Description'),
            'config' => Yii::t('auth', 'Configuration'),
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeHints()
    {
        return [
            'name' => \Yii::t('auth', 'The name of this authentication method. Could be the (short) name of the school, or just <code>LDAP</code> or <code>AD</code>'),
            'class' => \Yii::t('auth', 'Choose one of the available authentication methods from the list below.'),
            'order' => \Yii::t('auth', 'The order as a number in which the authentication process should process in case of multiple (non local) authentication methods.'),
            'migrateSearchPattern' => \Yii::t('auth', 'A search pattern to only include matching usernames for migration. Leave empty to inlcude all usernames.'),
        ];
    }

    /**
     * @inheritdoc
     */
    public function authenticate ($username, $password) {
        return false;
    }

    /**
     * @inheritdoc
     */
    public function queryUsers ($attribute, $params, $validator) {
        return;
    }

    /**
     * Creates a new entry in the [[migrateUsers]] array.
     *
     * @param array key value pair of substitutions
     * @return void
     */
    public function addMigrateUser ($params) {
        $key = substitute($this->migrateUsersKeyScheme, $params);
        $value = substitute($this->migrateUsersValueScheme, $params);
        $this->migrateUsers[$key] = $value;
    }

    /**
     * @return string description for the users being migrated form this auth method (shown in the form)
     */
    public function getMigrateFromDescription ()
    {
        return yiit('auth', 'Selected users will be migrated from {from} to {to}.');
    }

    /**
     * @return string description for the users being migrated to this auth method (shown in the form)
     */
    public function getMigrateToDescription ()
    {
        return '';
    }

    /**
     * Decides whether the username provided by the user matches the pattern to authenticate.
     *
     * @param string $username the username that was provided to the login form by the user attempting to login
     * @param string $scheme the login scheme
     * @param array $substitutions array of substitutions
     *
     * @return false|string the extracted username or false
     */
    public function getRealUsernameByScheme($username, $scheme, $substitutions) {
        //$regex = '([^\"\/\\\[\]\:\;\|\=\,\+\*\?\<\>]+)';
        $regex = '(.+)';
        $pattern = substitute($scheme, array_merge($substitutions, [
            'username' => '@@@@',
        ]));
        $pattern = preg_quote($pattern, '/');
        $pattern = str_replace('@@@@', $regex, $pattern);

        preg_match('/'.$pattern.'/', $username, $matches);

        if (array_key_exists(1, $matches)) {
            return $matches[1];
        }
        return false;
    }

    /**
     * Defines which properties should be substituted into strings.
     *
     * @return array Array of property names and values.
     */
    public function substitutionProperties()
    {
        return [];
    }

    /**
     * Performs the substitution of search filters and strings.
     *
     * @param string $string String to perform substitutions {placeholders}
     * @param array $params Array with additional substitution keys and values. These will
     * take preference over the defined ones
     * @return string Substituted string
     * @see [[substitutionProperties()]]
     */
    public function substitute($string, $params)
    {
        return substitute($string, array_merge($this->substitutionProperties(), $params));
    }

    /**
     * Searches the database for users that are migratable in the current scenario.
     *
     * @return array Array where the keys are the ids and the value are the usernames.
     */
    public function findMigratableUsers($condition = [])
    {
        // search for local usernames matching [[migrateSearchPattern]]
        $models = User::find()->select(['username', 'id'])
            ->andWhere(['type' => $this->migrateFrom])
            ->andWhere(['not', ['id' => 1]])
            ->andWhere(['like', 'username', $this->substitute($this->migrateSearchPattern, [])])
            ->andWhere($condition)
            ->all();

        $localUsers = array_combine(array_column($models, 'id'), array_column($models, 'username'));
        $c = count($localUsers);

        if ($c === 0) {
            $this->error = Yii::t('auth', 'Found no usernames matching the search pattern <code>{migrateSearchPattern}</code>.', [
                'migrateSearchPattern' => $this->substitute($this->migrateSearchPattern, ['username' => '{username}']),
            ]);
            Yii::debug($this->error, __METHOD__);
            $this->addError('migrateSearchPattern', \Yii::t('auth', 'Found no usernames matching search pattern.'));
        } else {
            $this->debug[] = Yii::t('auth', 'Found {n} usernames matching the search pattern <code>{migrateSearchPattern}</code>: <span class="show_more">{users}</span>', [
                'n' => $c,
                'migrateSearchPattern' => $this->substitute($this->migrateSearchPattern, ['username' => '{username}']),
                'users' => $c == 0 ? '' : '<code>' . implode('</code>, <code>', $localUsers) . '</code>',
            ]);
        }

        return $localUsers;
    }

    /**
     * Getter @todo remove
     */
    public function getConfigArray()
    {
        if(!isset($this->_configArray)){
            $this->_configArray = [
                'class' => $this->class,
                'name' => $this->name,
                'description' => $this->description,
                'form' => $this->form,
                'view' => $this->view,
                'type' => $this->type,
                'order' => $this->order,
            ];
        }
        return $this->_configArray;
    }

    /**
     * Setter @todo remove
     */
    public function setConfigArray($array)
    {
        $this->_configArray = $array;
    }

    /**
     * Getter for the list of authentication methods and their names
     */
    public function getAuthList()
    {
        return [
            'app\components\AuthGenericLdap' => \Yii::t('auth', 'Generic LDAP'),
            'app\components\AuthActiveDirectory' => \Yii::t('auth', 'Microsoft Active Directory'),
            'app\components\AuthOpenLdap' => \Yii::t('auth', 'OpenLDAP'),
        ];
    }

    /**
     * Getter for the configuration as it is in the config file with the
     * local db config prepended.
     * @return array|null the "methods" array element of the configuration array in `auth.php` or `null`
     * if the key does not exist.
     */
    public function getFileConfig()
    {
        $config = require(self::PATH . '/auth.php');

        // prepend the local auth method
        $local = new \app\components\Local();
        array_unshift($config['methods'], $local->configArray);

        return array_key_exists('methods', $config)
            ? $config['methods']
            : null;
    }

    /**
     * Saves the config to the file `auth.php`.
     * A temporary file called `auth.php` in `tmpPath` (@see params.php) is created first and required as
     * sanity check. If no exceptions are thrown, the original `auth.php` contents are moved to a backup
     * file called `auth_backup.bak` and the contents of `auth.php` are replaced with the new generated file 
     * contents.
     *
     * @param array $config the new/altered config array
     * @throws UnprocessableEntityHttpException if the temporary file could not be parsed without error
     * @return bool whether the saving succeeded.
     */
    public function saveFileConfig($config)
    {
        // remove the local db element from the array
        if (array_key_exists(0, $config)) {
            array_shift($config);
        }

        $prepend = '<?php

/**
 * Please to not edit this file.
 * This file was automatically generated using the web interface.
 */
return [
  \'class\' => \'app\models\Auth\',
  \'methods\' => 
';
        $append = "
];";
        $prefix = "    ";

        $newConfig = $prepend . preg_replace('/^/m', $prefix, var_export($config, true)) . $append;

        $tmpFile = FileHelper::normalizePath(\Yii::$app->params['tmpPath'] . '/auth.php');
        $bakFile = FileHelper::normalizePath(self::PATH . '/auth_backup.php');
        $file = FileHelper::normalizePath(self::PATH . '/auth.php');

        if (@file_put_contents($tmpFile, $newConfig)) {
            try {
                require($tmpFile);
            } catch (\ParseError $e) {
                $err = \Yii::t('auth', 'Failed to write auth.php config file: ParseError: {error} in file {file} at line {line}.', [
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]);
                Yii::error($err);
                throw new UnprocessableEntityHttpException($err);
            } catch (\yii\base\Exception $e) {
                $err = \Yii::t('auth', 'Failed to write auth.php config file: Exception: {error} in file {file} at line {line}.', [
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]);
                Yii::error($err);
                throw new UnprocessableEntityHttpException($err);
            }
            $oldConfig = file_get_contents($file);

            if (! @file_put_contents($bakFile, $oldConfig)) {
                $err = Yii::t('auth', 'Backup file {file} could not be written.', [
                    'file' => $bakFile,
                ]);
                $this->addError('*', $err);
                Yii::$app->session->addFlash('danger', $err);
                return false;
            }

            // write the new config file
            if (! @file_put_contents($file, $newConfig)) {
                $err = Yii::t('auth', 'Configuration file {file} could not be written.', [
                    'file' => $file,
                ]);
                $this->addError('*', $err);
                Yii::$app->session->addFlash('danger', $err);
                return false;
            }

            return true;
        } else {
            $err = Yii::t('auth', 'Temporary file {file} could not be written.', [
                'file' => $tmpFile,
            ]);
            $this->addError('*', $err);
            Yii::$app->session->addFlash('danger', $err);
        }
        return false;
    }

    /**
     * @todo remove
     * Getter for the instantiated object of app\components\AuthType
     * Possible objects are:
     *  * app\models\Auth
     *  * app\components\Auth*
     * 
     * @return app\models\Auth|app\components\Auth*|null instantiated object
     */
    public function getObj()
    {

        if(!isset($this->_obj)){
            $this->_obj = Yii::createObject($this->configArray);
        }
        return $this->_obj;
    }

    // @todo remove
    public function getConfig()
    {
        return json_encode($this->configArray);
    }

    /**
     * Whether the record is new and should be inserted when calling [[save()]].
     * @return bool
     */
    public function getIsNewRecord()
    {
        return !array_key_exists($this->id, $this->fileConfig);
    }

    /**
     * Deletes the config element corresponding to this model.
     *
     * @return int|false the number of elements deleted, or `false` if the deletion is unsuccessful for some reason.
     */
    public function delete()
    {
        if ($this->id == "0") {
            Yii::$app->session->addFlash('danger', \Yii::t('auth', 'The entry with id 0 cannot be deleted.'));
            return false;
        }

        $fileConfig = $this->fileConfig;
        unset($fileConfig[$this->id]);
        if (($this->saveFileConfig($fileConfig)) === true) {
            return 1;
        };
        return false;
    }

    /**
     *
     * @return void
     */
    public function insert($runValidation = true, $attributeNames = null)
    {
        if ($runValidation && !$this->validate($attributeNames)) {
            Yii::info('Model not inserted due to validation error.', __METHOD__);
            return false;
        }
        $this->id = $this->nextId();
        $configItem = $this->getAttributes($this->activeAttributes());
        $fileConfig = $this->fileConfig;
        $fileConfig[$this->id] = $configItem;
        if (($this->saveFileConfig($fileConfig)) === true) {
            return true;
        };
        return false;
    }

    /**
     * Generates the id for a new authentication method entry.
     * 
     * @return int the next id
     */
    private function nextId()
    {
        return generate_uuid();
        //return max(array_keys($this->fileConfig)) + 1;
    }

    /**
     *
     * @return void
     */
    public function update($runValidation = true, $attributeNames = null)
    {
        if ($runValidation && !$this->validate($attributeNames)) {
            Yii::info('Model not updated due to validation error.', __METHOD__);
            return false;
        }
        $configItem = $this->getAttributes($this->activeAttributes());
        $fileConfig = $this->fileConfig;
        $fileConfig[$this->id] = $configItem;
        if (($this->saveFileConfig($fileConfig)) === true) {
            return true;
        };
        return false;
    }

    /**
     * Saves the current record.
     *
     * This method will call [[insert()]] when [[isNewRecord]] is `true`, or [[update()]]
     * when [[isNewRecord]] is `false`.
     *
     * @param bool $runValidation whether to perform validation (calling [[validate()]])
     * before saving the record. Defaults to `true`. If the validation fails, the record
     * will not be saved to the config file and this method will return `false`.
     * @param array $attributeNames list of attribute names that need to be saved. Defaults to null,
     * meaning all attributes that are loaded from the config file will be saved.
     * @return bool whether the saving succeeded (i.e. no validation errors occurred).
     */
    public function save($runValidation = true, $attributeNames = null)
    {
        if ($this->getIsNewRecord()) {
            return $this->insert($runValidation, $attributeNames);
        }
        return $this->update($runValidation, $attributeNames) !== false;
    }

    /**
     * @todo remove
     * @return Auth
     */
    public function find()
    {
        return;
    }

    private function sortByOrder($a, $b) {
        return $a['order'] - $b['order'];
    }

    /**
     * 
     * @return Auth[] All Authentication elements
     */
    public function findAll($params = null)
    {
        $models = [];
        foreach ($this->fileConfig as $id => $configArray) {
            //var_dump($configArray);die();
            $model = new Auth();
            $model->configArray = $configArray;
            //$model->id = $id;
            //$model->name = $configArray['name'];
            $model->obj->id = $id;
            $models[] = $model->obj;
        }
        usort($models, [$this, 'sortByOrder']);
        return $models;
    }

    /**
     * @param int id
     * @return Auth Authentication instance matching the id, or null if nothing matches.
     */
    public function findOne($id)
    {
        $configArray = self::getFileConfig();
        if (array_key_exists($id, $configArray)) {
            $model = new Auth();
            $model->configArray = $configArray[$id];
            //$model->name = $configArray[$id]['name'];
            //$model->id = $id;
            $model->obj->id = $id;
            return $model->obj;
        } else {
            return null;
        }
    }
}