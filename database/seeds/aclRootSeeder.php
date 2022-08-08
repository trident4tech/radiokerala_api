<?php

use Illuminate\Database\Seeder;

class aclRootSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $data = [
/*            ['acl_rootes' => 'v1/groupuser/add', 'acl_name' => 'USER GROUP ADD'],
            ['acl_rootes' => 'v1/groupuser/list', 'acl_name' => 'USER GROUP LIST'],
            ['acl_rootes' => 'v1/groupuser/edit', 'acl_name' => 'USER GROUP EDIT'],
            ['acl_rootes' => 'v1/groupuser/view', 'acl_name' => 'USER GROUP VIEW'],
            ['acl_rootes' => 'v1/groupuser/delete', 'acl_name' => 'USER GROUP DELETE'],
            ['acl_rootes' => 'v1/groupuser/status', 'acl_name' => 'USER GROUP CHANGE STATUS'],
            ['acl_rootes' => 'v1/user/create', 'acl_name' => 'USER CREATE'],
            ['acl_rootes' => 'v1/user/login', 'acl_name' => 'USER LOGIN'],
            ['acl_rootes' => 'v1/user/logout', 'acl_name' => 'USER LOGOUT'],
            ['acl_rootes' => 'v1/user/view', 'acl_name' => 'USER VIEW'],
            ['acl_rootes' => 'v1/user/roleCreate', 'acl_name' => 'ROLE CREATE'],
            ['acl_rootes' => 'v1/user/roleList', 'acl_name' => 'ROLE LIST'],
            ['acl_rootes' => 'v1/user/searchUser', 'acl_name' => 'USER SEARCH'],
            ['acl_rootes' => 'v1/user/roleDelete', 'acl_name' => 'ROLE DELETE'],
       ['acl_rootes' => 'v1/user/permissionList', 'acl_name' => 'PERMISSTION LIST'],
       ['acl_rootes' => 'v1/user/givePermission', 'acl_name' => 'GIVE PERMISSION'],
       ['acl_rootes' => 'v1/user/removePermssion', 'acl_name' => 'REMOVE PERMISSION'],
       ['acl_rootes' => 'v1/user/viewPermissionRole', 'acl_name' => 'VIEW PERMISSION'],
       ['acl_rootes' => 'v1/user/settings', 'acl_name' => 'SETTINGS'],
       ['acl_rootes' => 'v1/user/edit', 'acl_name' => 'USER EDIT'],
       ['acl_rootes' => 'v1/user/view/user_get', 'acl_name' => 'USER VIEW'],
       ['acl_rootes' => 'v1/user/delete', 'acl_name' => 'USER DELETE'],
       ['acl_rootes' => 'v1/user/status', 'acl_name' => 'USER CHANGE STATUS'],
       ['acl_rootes' => 'v1/user/forget', 'acl_name' => 'USER FORGOT PASSWORD'],
       ['acl_rootes' => 'v1/user/reset', 'acl_name' => 'USER RESET PASSWORD'],
       ['acl_rootes' => 'v1/user/passwordreset', 'acl_name' => 'USER'],
       ['acl_rootes' => 'v1/master/data/list', 'acl_name' => 'MASTER LIST'],
       ['acl_rootes' => 'v1/master/data/create', 'acl_name' => 'MASTER CREATE'],
       ['acl_rootes' => 'v1/master/data/edit', 'acl_name' => 'MASTER EDIT'],
       ['acl_rootes' => 'v1/master/data/import', 'acl_name' => 'MASTER IMPORT'],
       ['acl_rootes' => 'v1/master/data/delete', 'acl_name' => 'MASTER DELETE'],
       ['acl_rootes' => 'v1/master/data/view', 'acl_name' => 'MASTER VIEW'],
       ['acl_rootes' => 'v1/ticketing/data/ca/create', 'acl_name' => 'TICKET CREATE'],

       ['acl_rootes' => 'v1/ticketing/data/link_counter', 'acl_name' => 'LINK COUNTER'],
       ['acl_rootes' => 'v1/ticketing/data/list_counter', 'acl_name' => 'LIST COUNTER'],
       ['acl_rootes' => 'v1/ticketing/data/delete_counter', 'acl_name' => 'DELETE TICKET'],
       ['acl_rootes' => 'v1/ticketing/data/create_class', 'acl_name' => 'CREATE TICKET'],
       ['acl_rootes' => 'v1/ticketing/data/list_class', 'acl_name' => 'LIST TICKET'],
       ['acl_rootes' => 'v1/ticketing/data/edit_class', 'acl_name' => 'EDIT CLASS'],
       ['acl_rootes' => 'v1/ticketing/data/delete_class', 'acl_name' => 'DELETE CLASS'],
       ['acl_rootes' => 'v1/ticketing/data/view_ticket', 'acl_name' => 'VIEW TICKET'],
       ['acl_rootes' => 'v1/ticketing/data/list_ticket', 'acl_name' => 'LIST TICKET'],
       ['acl_rootes' => 'v1/ticketing/data/delete_ticket', 'acl_name' => 'DELETE TICKET'],
       ['acl_rootes' => 'v1/ticketing/data/create_ticket_class', 'acl_name' => 'CREATE TICKET CLASS'],
       ['acl_rootes' => 'v1/ticketing/data/searchTicket', 'acl_name' => 'SEARCH TICKET'],
       ['acl_rootes' => 'v1/ticketing/data/oc_counter', 'acl_name' => 'OPEN CLOSE COUNTER'],
       ['acl_rootes' => 'v1/ticketing/data/sync', 'acl_name' => 'SYNC'],
       ['acl_rootes' => 'v1/ticketing/data/verification', 'acl_name' => 'VERIFICATION TICKET'],
       ['acl_rootes' => 'v1/master/data/status', 'acl_name' => 'STATUS CHANGE'],
       ['acl_rootes' => 'v1/file/upload', 'acl_name' => 'FILE UPLOAD'],
       ['acl_rootes' => 'v1/file/create', 'acl_name' => 'FILE CREATE'],
       ['acl_rootes' => 'v1/constant/create', 'acl_name' => 'CONSTANT CREATE'],
       ['acl_rootes' => 'v1/constant/edit', 'acl_name' => 'CONSTANT EDIT'],
       ['acl_rootes' => 'v1/constant/delete', 'acl_name' => 'CONSTANT '],
       ['acl_rootes' => 'v1/constant/list', 'acl_name' => 'CONSTANT LIST'],
       ['acl_rootes' => 'v1/price/create', 'acl_name' => 'PRICE CREATE'],
       ['acl_rootes' => 'v1/price/edit', 'acl_name' => 'PRICE EDIT'],
       ['acl_rootes' => 'v1/price/delete', 'acl_name' => 'PRICE DELETE'],
       ['acl_rootes' => 'v1/ticketing/data/report', 'acl_name' => 'DATA DELETE'],*/
       ['acl_rootes' => 'home', 'acl_name' => ''],
       ['acl_rootes' => 'usergroup-create', 'acl_name' => ''],

       ['acl_rootes' => 'usergroup-edit', 'acl_name' => ''],

       ['acl_rootes' => 'usergroup-list', 'acl_name' => ''],

       ['acl_rootes' => 'register', 'acl_name' => ''],

       ['acl_rootes' => 'destination', 'acl_name' => ''],

       ['acl_rootes' => 'admin-level-user-management', 'acl_name' => ''],

       ['acl_rootes' => 'user', 'acl_name' => ''],

       ['acl_rootes' => 'user-profile', 'acl_name' => ''],

       ['acl_rootes' => 'booking', 'acl_name' => ''],

       ['acl_rootes' => 'offline-sync', 'acl_name' => ''],

       ['acl_rootes' => 'login', 'acl_name' => ''],

       ['acl_rootes' => 'listing', 'acl_name' => ''],

       ['acl_rootes' => 'verification', 'acl_name' => ''],

       ['acl_rootes' => 'acl', 'acl_name' => ''],

       ['acl_rootes' => 'destinationuser', 'acl_name' => ''],

       ['acl_rootes' => 'ticket-verification-sample', 'acl_name' => ''],
       ['acl_rootes' => 'ticket-view', 'acl_name' => ''],

       ['acl_rootes' => 'dynamic-settings', 'acl_name' => ''],

       ['acl_rootes' => 'ticket-class-management', 'acl_name' => ''],

       ['acl_rootes' => 'logout', 'acl_name' => ''],





        ];
        DB::table('acl_rootes')->insert($data);
        //
    }
}
