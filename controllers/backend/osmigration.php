<?php

class Osmigration_Backend_Osmigration_Controller extends Admin_Controller {

    public function __construct()
    {
        parent::__construct();
        
        $this->data['section_bar'] = array(
            __('osmigration::lang.OS Migration')->get(ADM_LANG) => URL::base().'/'.ADM_URI.'/osmigration',
        );

        $this->data['bar'] = array(
            'title'       => __('osmigration::lang.OS Migration')->get(ADM_LANG),
            'url'         => URL::base().'/'.ADM_URI.'/osmigration',
            'description' => __('osmigration::lang.Manage migration of users from an existend grid to MWI application.')->get(ADM_LANG),
        );

        $db_is_ready = Config::get('settings::core.passes_db_settings');
    }

    public function get_index()
    {
        $this->data['section_bar_active'] = Lang::line('osmigration::lang.OS Migration')->get(ADM_LANG);
        
        $db_is_ready = Config::get('settings::core.passes_db_settings');
        if( !(bool)$db_is_ready)
        {
            $this->data['import'] = array();
            $this->data['import']['tabs'] = array();
            $this->data['message']      = __('osmigration::lang.The database of the Opensim module must be configured')->get(APP_LANG);
            $this->data['message_type'] = 'error';
            return $this->theme->render('osmigration::backend.index', $this->data);
        }

        $grid_to_app   = true;
        $fails_count   = 0;
        $total_users   = 0;
        $imports_count = 0;

        $os_users = \Opensim\Model\Os\UserAccount::get(array('FirstName', 'LastName', 'Email', 'PrincipalID'));
        $grid_users_array = array();
        foreach ($os_users as $user) 
        {
            $grid_users_array[$user->principalid] = array(
                'uuid'      => $user->principalid,
                'firstname' => $user->firstname,
                'lastname'  => $user->lastname,
                'email'     => $user->email,
            );
        }

        $app_users = \Users\Model\User::get(array('avatar_first_name', 'avatar_last_name', 'email', 'uuid'));
        $app_users_array = array();
        foreach ($app_users as $user) 
        {
            $app_users_array[$user->uuid] = array(
                'uuid'      => $user->uuid,
                'firstname' => $user->avatar_first_name,
                'lastname'  => $user->avatar_last_name,
                'email'     => $user->email,
            );
        }
        
        $this->data['import'] = $this->validate_users($grid_users_array, $app_users_array);
        $this->data['groups'] = Groups\Model\Group::get(array('id', 'name'));

        return $this->theme->render('osmigration::backend.index', $this->data);
    }

    public function post_create()
    {
        $db_is_ready = Config::get('settings::core.passes_db_settings');
        if( !(bool)$db_is_ready)
        {
            return Redirect::to(ADM_URI.'/osmigration')->with($this->data);
        }

        $os_users = \Opensim\Model\Os\UserAccount::get(array('FirstName', 'LastName', 'Email', 'PrincipalID'));
        $grid_users_array = array();
        foreach ($os_users as $user) 
        {
            $grid_users_array[$user->principalid] = array(
                'uuid'      => $user->principalid,
                'firstname' => $user->firstname,
                'lastname'  => $user->lastname,
                'email'     => $user->email,
            );
        }

        $app_users = \Users\Model\User::get(array('avatar_first_name', 'avatar_last_name', 'email', 'uuid'));
        $app_users_array = array();
        foreach ($app_users as $user) 
        {
            $app_users_array[$user->uuid] = array(
                'uuid'      => $user->uuid,
                'firstname' => $user->avatar_first_name,
                'lastname'  => $user->avatar_last_name,
                'email'     => $user->email,
            );
        }
        
        $import = $this->validate_users($grid_users_array, $app_users_array);
        
        $total_users = 0;

        $post_data   = Input::all();
        $group_id    = isset($post_data['group']) ? $post_data['group'] : 0;
        $count       = isset($post_data['take']) ? $post_data['take'] : 50;
        
        foreach ($import['users'] as $import) 
        {
            if($import['user']['status'] == 'migrationok' and $total_users < $count)
            {
                ++$total_users;
                $new_user = new \Users\Model\User;
                $new_user->group_id          = $post_data['group'];
                $new_user->uuid              = $import['user']['uuid'];
                $new_user->username          = $import['user']['firstname'].$import['user']['lastname'];
                $new_user->avatar_first_name = $import['user']['firstname'];
                $new_user->avatar_last_name  = $import['user']['lastname'];
                $new_user->email             = $import['user']['email'];
                $new_user->status            = 'active';
                $new_user->is_core           = 0;
                $new_user->save();
            }
        }

        $this->data['message']      = __('osmigration::lang.:total_users users were successfull migrated.', array('total_users' => $total_users))->get(APP_LANG);
        $this->data['message_type'] = 'success';
        return Redirect::to(ADM_URI.'/osmigration')->with($this->data);
    }

    private function validate_users($migration_users, $app_users)
    {
        $result        = array();
        $total_count   = 0;
        $fails_count   = 0;
        $imports_count = 0;
        $sync_count    = 0;
        $mismatch      = 0;

        if(isset($migration_users) and !empty($migration_users))
        {
            $rules = array(
                'email'     => 'required|email',
                'firstname' => 'required|min:3|alpha_dash',
                'lastname'  => 'required|min:3|alpha_dash',
            );

            foreach ($migration_users as $uuid => $user) 
            {
                $validation = Validator::make($user, $rules);
                
                if($validation->passes())
                {
                    if(isset($app_users[$uuid]))
                    {
                        $mismatch = false;

                        if($app_users[$uuid]['firstname'] != $user['firstname'])
                        {
                            $result['users'][$uuid]['user']            = $user;
                            $result['users'][$uuid]['user']['message'] = '<span class="small">The importing first name: '.$user['firstname'].' does not match with existing user first name: '.$app_users[$uuid]['firstname'].'</span><br />';
                            $result['users'][$uuid]['user']['status']  = 'mismatch';
                            $result['tabs']['mismatch']                = 'Mismatches';
                            $mismatch                                  = true;
                        }

                        if($app_users[$uuid]['lastname'] != $user['lastname'])
                        {
                            $result['users'][$uuid]['user']            = $user;
                            $result['users'][$uuid]['user']['message'] = '<span class="small">The importing last name: '.$user['lastname'].' does not match with existing user last name: '.$app_users[$uuid]['lastname'].'</span><br />';
                            $result['users'][$uuid]['user']['status']  = 'mismatch';
                            $result['tabs']['mismatch']                = 'Mismatches';
                            $mismatch                                  = true;
                        }

                        if($app_users[$uuid]['email'] != $user['email'])
                        {
                            $result['users'][$uuid]['user']            = $user;
                            $result['users'][$uuid]['user']['message'] = '<span class="small">The importing user email: '.$user['email'].' does not match with existing user email: '.$app_users[$uuid]['email'].'</span><br />';
                            $result['users'][$uuid]['user']['status']  = 'mismatch';
                            $result['tabs']['mismatch']                = 'Mismatches';
                            $mismatch                                  = true;
                        }

                        if( !$mismatch)
                        {
                            $result['users'][$uuid]['user']            = $user;
                            $result['users'][$uuid]['user']['status']  = 'insync';
                            $result['users'][$uuid]['user']['message'] = 'Alread Exists';
                            $result['tabs']['insync']                  = 'In Sync';
                        }
                    }
                    else
                    {
                        // uuid was not found check for 
                        // existing users first name and last name
                        $importing_full_name = $user['firstname'].' '.$user['lastname'];

                        foreach ($app_users as $appuuid => $appuser) 
                        {
                            $existing_full_name = $appuser['firstname'].' '.$appuser['lastname'];
                            
                            $isok = true;

                            if($importing_full_name == $existing_full_name)
                            {
                                $result['users'][$uuid]['user']            = $user;
                                $result['users'][$uuid]['user']['message'] = '<span class="small">The importing user ['.$importing_full_name.'] uuid: [' .$uuid.'] differs from the existing user uuid: ['.$appuuid.'], but they have the same combination of first name and last name : '.$existing_full_name.'</span><br />';
                                $result['users'][$uuid]['user']['status']  = 'mismatch';
                                $result['tabs']['mismatch']                = 'Mismatches';
                                $isok                                      = false;

                            }

                            if($appuser['email'] == $user['email'])
                            {
                                $result['users'][$uuid]['user']            = $user;
                                $result['users'][$uuid]['user']['message'] = '<span class="small">The importing user email: ['.$user['email'].'] are the same as the existing user email: ['.$appuser['email'].']</span><br />';
                                $result['users'][$uuid]['user']['status']  = 'mismatch';
                                $result['tabs']['mismatch']                = 'Mismatches';
                                $isok                                      = false;
                            }

                            if($isok)
                            {
                                $result['users'][$uuid]['user']            = $user;
                                $result['users'][$uuid]['user']['message'] = 'Ready to Migrate';
                                $result['users'][$uuid]['user']['status']  = 'migrationok';
                                $result['tabs']['migrationok']             = 'Ready for Migration';
                            }
                        }
                    }
                }
                else
                {
                    $result['users'][$uuid]['user']            = $user;
                    $result['users'][$uuid]['user']['status']  = 'failed';
                    $result['users'][$uuid]['user']['message'] = implode(' ', $validation->errors->all('<span class="small">:message</span><br />'));
                    $result['tabs']['failed']                  = 'Won\'t Migrate';
                }
            }
        }

        return $result;
    }
}