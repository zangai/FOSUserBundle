<?php

/**
 * (c) Thibault Duplessis <thibault.duplessis@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace FOS\UserBundle\Controller;

use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\HttpFoundation\RedirectResponse;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

use FOS\UserBundle\Model\UserInterface;

/**
 * RESTful controller managing user CRUD
 */
class UserController extends ContainerAware
{
    /**
     * Show all users
     */
    public function listAction()
    {
        $users = $this->container->get('fos_user.user_manager')->findUsers();

        return $this->container->get('templating')->renderResponse('FOSUserBundle:User:list.html.'.$this->getEngine(), array('users' => $users));
    }

    /**
     * Show one user
     */
    public function showAction($username)
    {
        $user = $this->findUserBy('username', $username);

        return $this->container->get('templating')->renderResponse('FOSUserBundle:User:show.html.'.$this->getEngine(), array('user' => $user));
    }

    /**
     * Edit one user, show the edit form
     */
    public function editAction($username)
    {
        $user = $this->findUserBy('username', $username);
        $form = $this->container->get('fos_user.form.user');
        $formHandler = $this->container->get('fos_user.form.handler.user');

        $process = $formHandler->process($user);
        if ($process) {
            $this->setFlash('fos_user_user_update', 'success');
            $userUrl =  $this->container->get('router')->generate('fos_user_user_show', array('username' => $user->getUsername()));

            return new RedirectResponse($userUrl);
        }

        return $this->container->get('templating')->renderResponse('FOSUserBundle:User:edit.html.'.$this->getEngine(), array(
            'form'      => $form->createView(),
            'username'  => $user->getUsername()
        ));
    }

    /**
     * Delete one user
     */
    public function deleteAction($username)
    {
        $user = $this->findUserBy('username', $username);
        $this->container->get('fos_user.user_manager')->deleteUser($user);
        $this->setFlash('fos_user_user_delete', 'success');

        return new RedirectResponse( $this->container->get('router')->generate('fos_user_user_list'));
    }

    /**
     * Change user password: show form
     */
    public function changePasswordAction()
    {
        $user = $this->getUser();
        $form = $this->container->get('fos_user.form.change_password');
        $formHandler = $this->container->get('fos_user.form.handler.change_password');
        $formHandler->process($user);

        return $this->container->get('templating')->renderResponse('FOSUserBundle:User:changePassword.html.'.$this->getEngine(), array(
            'form' => $form->createView()
        ));
    }

    /**
     * Change user password: submit form
     */
    public function changePasswordUpdateAction()
    {
        $user = $this->getUser();
        $form = $this->container->get('fos_user.form.change_password');
        $formHandler = $this->container->get('fos_user.form.handler.change_password');

        $process = $formHandler->process($user);
        if ($process) {
            $this->setFlash('fos_user_user_password', 'success');
            $url =  $this->container->get('router')->generate('fos_user_user_show', array('username' => $user->getUsername()));
            return new RedirectResponse($url);
        }

        return $this->container->get('templating')->renderResponse('FOSUserBundle:User:changePassword.html.'.$this->getEngine(), array(
            'form' => $form->createView()
        ));
    }

    /**
     * Get a user from the security context
     *
     * @throws AccessDeniedException if no user is authenticated
     * @return User
     */
    protected function getUser()
    {
        $user = $this->container->get('security.context')->getToken()->getUser();
        if (!is_object($user) || !$user instanceof UserInterface) {
            throw new AccessDeniedException('This user does not have access to this section.');
        }

        return $user;
    }

    /**
     * Find a user by a specific property
     *
     * @param string $key property name
     * @param mixed $value property value
     * @throws NotFoundException if user does not exist
     * @return User
     */
    protected function findUserBy($key, $value)
    {
        if (!empty($value)) {
            $user = $this->container->get('fos_user.user_manager')->{'findUserBy'.ucfirst($key)}($value);
        }

        if (empty($user)) {
            throw new NotFoundHttpException(sprintf('The user with "%s" does not exist for value "%s"', $key, $value));
        }

        return $user;
    }

    protected function setFlash($action, $value)
    {
        $this->container->get('session')->setFlash($action, $value);
    }

    protected function getEngine()
    {
        return $this->container->getParameter('fos_user.template.engine');
    }
}
