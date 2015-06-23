<?php
/**
 * CakeManager (http://cakemanager.org)
 * Copyright (c) http://cakemanager.org
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) http://cakemanager.org
 * @link          http://cakemanager.org CakeManager Project
 * @since         1.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Users\Model\Table;

use Cake\Core\Configure;
use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use Users\Model\Entity\User;

/**
 * Users Model
 */
class UsersTable extends Table
{

    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config)
    {
        $this->table('users');
        $this->displayField('email');
        $this->primaryKey('id');
        $this->addBehavior('Timestamp');

        $this->belongsTo('Users.Roles');

        $this->postType = [
            'formFields' => [
                'id',
                'email',
                'new_password' => [
                    'type' => 'password',
                    'help' => 'help!'
                ],
                'confirm_password' => [
                    'type' => 'password'
                ],
                'role_id',
                'active' => [
                    'type' => 'checkbox'
                ],
            ],
            'tableColumns' => [
                'id' => [],
                'email' => [],
                'created' => []
            ],
            'filters' => [
                'email'
            ]
        ];
    }

    public function beforeFind($event, $query, $options, $primary)
    {
        $query->where(['Users.cakeadmin' => 0]);
    }

    /**
     * Default validation rules.
     *
     * @param \Cake\Validation\Validator $validator Validator instance.
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator)
    {
        $validator
            ->add('id', 'valid', ['rule' => 'numeric'])
            ->allowEmpty('id', 'create');

        $validator
            ->add('email', 'valid', ['rule' => 'email'])
            ->allowEmpty('email');

        $validator
            ->allowEmpty('password');

        $validator
            ->add('active', 'valid', ['rule' => 'numeric'])
            ->allowEmpty('active');

        $validator
            ->allowEmpty('usercode');

        $validator
            ->allowEmpty('new_password');


        $validator
            ->allowEmpty('confirm_password');

        $validator
            ->add('new_password', 'custom', [
                'rule' => function ($value, $context) {
                    if ($value !== $context['data']['confirm_password']) {
                        return false;
                    }
                    return true;
                },
                'message' => __('Passwords are not equal.'),
            ]);

        $validator
            ->add('confirm_password', 'custom', [
                'rule' => function ($value, $context) {
                    if ($value !== $context['data']['new_password']) {
                        return false;
                    }
                    return true;
                },
                'message' => __('Passwords are not equal.'),
            ]);

        return $validator;
    }

    /**
     * Returns a rules checker object that will be used for validating
     * application integrity.
     *
     * @param \Cake\ORM\RulesChecker $rules The rules object to be modified.
     * @return \Cake\ORM\RulesChecker
     */
    public function buildRules(RulesChecker $rules)
    {
        $rules->add($rules->isUnique(['email']));
        return $rules;
    }

    /**
     * beforeSave event
     *
     * @param \Cake\Event\Event $event Event.
     * @param \Cake\ORM\Entity $entity Entity.
     * @param array $options Options.
     * @return void
     */
    public function beforeSave(Event $event, $entity, $options)
    {
        if (!empty($entity->get('new_password'))) {
            $entity->set('password', $entity->new_password); // set for password-changes
        }
    }

    /**
     * generateRequestKey
     *
     * This method generates a request key for an user.
     * It returns a generated string.
     *
     * @return string
     */
    public function generateRequestKey()
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $requestKey = '';
        for ($i = 0; $i < 40; $i++) {
            $requestKey .= $characters[rand(0, $charactersLength - 1)];
        }
        return $requestKey;
    }

    /**
     * validateRequestKey
     *
     * Checks if an user is allowed to do an action with a required activation-key
     *
     * @param string $email E-mailaddress of the user.
     * @param string $activationKey Activation key of the user.
     * @return bool
     */
    public function validateRequestKey($email, $requestKey = null)
    {
        if (!$requestKey) {
            return false;
        }

        $field = Configure::read('Users.fields.username');
        $query = $this->find('all')->where([
            $field => $email,
            'request_key' => $requestKey
        ]);

        if ($query->Count() > 0) {
            return true;
        }
        return false;
    }

}