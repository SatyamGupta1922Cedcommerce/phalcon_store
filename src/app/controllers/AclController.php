<?php

use Phalcon\Mvc\Controller;
use Phalcon\Acl\Adapter\Memory;
use Phalcon\Security\JWT\Signer\Hmac;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class AclController extends Controller
{
    public function IndexAction()
    {
    }
    public function RegisterroleAction()
    {
        $role = new Roles();
        $role->assign(
            $this->request->get(),
            [
                'role'
            ]
        );

        $success = $role->save();

        $this->view->success = $success;

        if ($success) {
            $this->view->message = "Register succesfully";
        } else {
            $this->view->message = "Not Register succesfully due to following reason: <br>" . implode("<br>", $role->getMessages());
        }
        $this->response->redirect('/');
    }
    public function RegistercomponentAction()
    {
        if ($this->request->get('controller')) {
            $component = new Addcontrols();
            $component->assign(
                $this->request->get(),
                [
                    'controller', 'action'
                ]
            );

            $success = $component->save();

            $this->view->success = $success;

            if ($success) {
                $this->view->message = "Register succesfully";
            } else {
                $this->view->message = "Not Register succesfully due to following reason: <br>" . implode("<br>", $role->getMessages());
            }
            $this->response->redirect('/');
        }
    }
    public function allowuserAction()
    {
    }
    public function adduserAction()
    {
        if ($this->request->get('role')) {
            $user = new Users();
            $signer  = new Hmac();
            $role = $this->request->getPost('role');
            $name = $this->request->getPost('name');
            $key = "example_key";
            $payload = array(
                "iss" => "http://example.org",
                "aud" => "http://example.com",
                "iat" => 1356999524,
                "nbf" => 1357000000,
                'name' => $name,
                'role' => $role
            );
            $jwt = JWT::encode($payload, $key, 'HS256');

            $user->assign(
                $this->request->getPost(),
                [
                    'name',
                    'email',
                    'password',
                   

                ],
                $user->role = $jwt
            );

            $success = $user->save();
            $this->logger->getAdapter('register')->begin();

            $this->logger->alert('user successfully added!!');

            $this->logger->getAdapter('register')->commit();
            header('Location: http://localhost:8080/');
        }
    }



    public function buildaclAction()
    {

        $aclfile = APP_PATH . '/security/acl.cache';
        if (true !== is_file($aclfile)) {
            $acl = new Memory();

            $acl->addRole("admin");
            $acl->addComponent("Index", [
                'index'
            ]);
            $acl->allow("admin", "*", "*");
            file_put_contents($aclfile, serialize($acl));
        } else {
            $acl = unserialize(file_get_contents($aclfile));
            $arr = $this->request->getPost();
            $acl->addRole($arr['role']);
           foreach ($arr['component'] as $key => $value) {
                $componentObj = Addcontrols::find($value);
                $acl->addComponent(
                    $componentObj[0]->controller,
                    [
                        $componentObj[0]->action
                    ]
                );
            }
            foreach ($arr['component'] as $key => $value) {
                $componentObj = Addcontrols::find($value);

                $acl->allow($arr['role'], $componentObj[0]->controller, $componentObj[0]->action);
            }
            file_put_contents($aclfile, serialize($acl));
            header("Location: http://localhost:8080/");
        }
    }
}
