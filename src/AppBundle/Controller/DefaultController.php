<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\HttpFoundation\Session\Session;

use AppBundle\Entity\book;

class DefaultController extends Controller
{
    /**
     * @Route("/", name="homepage")
     */
    public function indexAction(Request $request)
    {
        $session = new Session();
        $comments = $this->getDoctrine()->getRepository('AppBundle:book')->findAll();

        /**
         * @var $paginator \Knp\Component\Pager\Paginator
         */
        $paginator = $this->get('knp_paginator');
        $result = $paginator->paginate(
            $comments,
            $request->query->getInt('page', 1),
            $request->query->getInt('limit', 10)
        );


        $book = new book();

        $form = $this->createFormBuilder($book)
            ->add('username', TextType::class, array('attr'=> array('class'=> 'form-control')))
            ->add('message', TextareaType::class, array('attr'=> array('class'=> 'form-control')))
            ->add('Save', SubmitType::class, array('label'=> 'Post comment!', 'attr'=> array('class'=> 'btn btn-primary', 'style'=> 'margin-top: 20px')))
            ->getForm();
        $form->handleRequest($request);


        if ($form->isSubmitted() && $form->isValid())
        {

            $username = $form['username']->getData();
            $message = $form['message']->getData();

            $now = new\DateTime('now');

            $book->setUsername($username);
            $book->setMessage($message);
            $book->setPostedDate($now);
            $em = $this->getDoctrine()->getManager();

            $em->persist($book);
            $em->flush();
            $this->addFlash(
                'notice','Comment posted!'
            );
            return $this->redirectToRoute('homepage');

        }

        return $this->render('comment/index.html.twig', array(
            'comments'=> $result,
            'form'=>$form->createView(),
            'logged' => $session->get('logged')
        ));
    }
    /**
     * @Route("/admin", name="admin")
     */
    public function adminAction(Request $request)
    {
        $session = new Session();

        if ($session->get('logged') == 1)
        {
            return $this->redirectToRoute('homepage');
        }

        $form = $this->createFormBuilder()
            ->add('username', TextType::class, array('attr'=> array('class'=> 'form-control', 'value'=> 'Admin')))
            ->add('password', PasswordType::class, array('label'=> 'Password', 'attr'=> array('class'=> 'form-control', 'value'=>'password')))
            ->add('Save', SubmitType::class, array('label'=> 'Login in', 'attr'=> array('class'=> 'btn btn-primary', 'style'=> 'margin-top: 20px')))
            ->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid())
        {
            $username = $form['username']->getData();
            $password = $form['password']->getData();

            if($username == "Admin" && $password == "password")
            {
                $session->set('logged', 1);
                return $this->redirectToRoute('homepage');
            }
            else
            {
                $this->addFlash(
                    'error','Username and/or password is wrong!'
                );
            }
        }

        return $this->render('comment/admin.html.twig', array(
            'form'=> $form->createView(),
            'logged' => $session->get('logged')
        ));
    }

    /**
     * @Route("/delete/{id}", name="comment_delete")
     */
    public function deleteAction($id)
    {
        $session = new Session();

        if ($session->get('logged') != 1)
        {
            return $this->redirectToRoute('homepage');
        }

        $em = $this->getDoctrine()->getManager();

        $book = $em->getRepository('AppBundle:book')->find($id);

        $em->remove($book);
        $em->flush();

        $this->addFlash(
            'notice','Comment was deleted!'
        );
        return $this->redirectToRoute('homepage');
    }

    /**
     * @Route("/logout", name="logout")
     */
    public function logoutAction()
    {
        $session = new Session();

        $session->set('logged', 0);

        return $this->redirectToRoute('homepage');
    }

}
