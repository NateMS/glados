<?php

/**
 * Please to not edit this file.
 * This file was automatically generated using the web interface.
 */
return [
  'class' => 'app\models\Auth',
  'methods' => 
    array (
      0 => 
      array (
        'class' => 'app\\components\\Ad',
        'name' => 'KSZ',
        'description' => 'KSZ description',
        'domain' => 'kszofingen.local',
        'ldap_uri' => 'ldap://172.10.0.4:389 ldap://172.10.0.15:389',
        'mapping' => 
        array (
          'Administratoren' => 'admin',
          'Teacher' => 'teacher',
        ),
        'loginScheme' => '{username}@ksz',
        'bindScheme' => '{username}@{domain}',
        'searchFilter' => '(sAMAccountName={username})',
      ),
      1 => 
      array (
        'class' => 'app\\components\\Ad',
        'name' => 'BWZ',
        'description' => 'Active Directory Authentication Method',
        'domain' => 'bwzofingen.local',
        'ldap_uri' => 'ldap://bwzofingen.local:389',
        'loginScheme' => '{username}@bwz',
        'bindScheme' => '{username}@{domain}',
        'searchFilter' => '(sAMAccountName={username})',
        'groupIdentifier' => 'sAMAccountName',
        'groupSearchFilter' => '(objectCategory=group)',
        'mapping' => 
        array (
          'LG-IT-Admins' => 'admin',
          'Ticket-Agent' => 'teacher',
          'LG-Lehrer-BFS' => 'teacher',
          'Administratoren' => 'teacher',
        ),
      ),
      2 => 
      array (
        'class' => 'app\\components\\Ad',
        'name' => 'LDAP1',
        'description' => 'Test Active Directory connection 1',
        'domain' => 'test.local',
        'ldap_uri' => 'ldap://192.168.0.67:389',
        'mapping' => 
        array (
          'admins' => 'admin',
          'teachers' => 'teacher',
        ),
        'loginScheme' => '{username}',
        'bindScheme' => '{username}@{domain}',
        'searchFilter' => '(sAMAccountName={username})',
      ),
      3 => 
      array (
        'class' => 'app\\components\\Ad',
        'name' => 'LDAP2',
        'description' => 'Test Active Directory connection 2',
        'domain' => 'test.local',
        'ldap_uri' => 'ldap://192.168.0.67:389',
        'mapping' => 
        array (
          'teachers' => 'teacher',
        ),
        'loginScheme' => '{username}@teacher',
        'bindScheme' => '{username}@{domain}',
        'searchFilter' => '(sAMAccountName={username})',
      ),
      4 => 
      array (
        'class' => 'app\\components\\Ad',
        'name' => 'ADTEST',
        'description' => 'balbla',
        'domain' => 'test2.local',
        'ldap_uri' => 'ldap://test2.local:389',
        'loginScheme' => '{username}',
        'bindScheme' => '{username}@{domain}',
        'searchFilter' => '(sAMAccountName={username})',
        'groupIdentifier' => 'sAMAccountName',
        'groupSearchFilter' => '(objectCategory=group)',
        'mapping' => 
        array (
          'test' => 'admin',
          'testteach' => 'teacher',
        ),
      ),
    )
];