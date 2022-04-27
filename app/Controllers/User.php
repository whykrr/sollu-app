<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\UserModel;
use App\Entities\User as EntitiesUser;
use Myth\Auth\Authorization\GroupModel;

class User extends BaseController
{
    /**
     * show page list
     */
    public function index()
    {
        $data['sidebar_active'] = 'user';
        return view('user/list', $data);
    }

    /**
     * show form 
     */
    public function form($id = null)
    {
        // get model
        $model = new UserModel();
        $modelGroup = new GroupModel();
        $data = [];

        // get groups
        $data['groups'] = (array)$modelGroup->findAll();

        // check id if not null
        if ($id != null) {
            // get user
            $user = $model->find($id);
            $data['edit'] = $user->toArray();
            $data['edit']['group'] = $user->getRoles();
        }
        return view('user/_form', $data);
    }

    /**
     * save data
     */
    public function save()
    {
        // get model
        $model = new UserModel();
        $modelGroup = new GroupModel();
        $auth = service('authentication');
        $data = $this->request->getPost();

        // make sure create user
        if (!isset($data['id'])) {
            // validation for password and password confirmation
            if ($data['password'] != $data['password_confirm']) {
                $json = [
                    'message' => 'validation error.',
                    'validation_error' => [
                        'password_confirm' => 'Konfirmasi password tidak sama.',
                    ],
                ];
                return $this->respond($json, 400);
            }
        } else {
            // attemp login 
            $login = $this->request->getPost('login');
            $password = $this->request->getPost('password');

            if (!$auth->validate(['username' => $login, 'password' => $password], false)) {
                $json = [
                    'message' => 'validation error',
                    'validation_error' => [
                        'password' => 'Password salah.',
                    ],
                ];
                return $this->respond($json, 400);
            }

            // remove user from group
            $modelGroup->removeUserFromAllGroups($data['id']);
        }

        //set username as email
        $data['email'] = $data['username'];

        // instance entities user
        $user = new EntitiesUser($data);
        $user->activate();

        $users = $model->withGroup($data['group']);
        if (isset($data['id'])) {
            $users->addToGroup($data);
        }

        //save data
        if (!$users->save($user)) {
            $json = [
                'message' => 'validation error.',
                'validation_errors' => $users->getValidationErrors(),
            ];
            return $this->respond($json, 400);
        }

        return $this->respond(['message' => 'Data berhasil disimpan.'], 200);
    }

    /**
     * delete data
     */
    public function delete($id)
    {
        // get model
        $model = new UserModel();

        // delete data
        if (!$model->delete($id)) {
            // get validation error message
            $errors = $model->errors();
            $json = [
                "message" => "validation error",
                "validation_error" => $errors,
            ];

            return $this->respond($json, 400);
        }

        $json = [
            "message" => 'Data berhasil dihapus',
        ];

        // redirect to list
        return $this->respond($json, 200);
    }
}
