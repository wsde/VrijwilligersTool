<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use AppBundle\Entity\Person;
use AppBundle\Entity\Form\PersonType;
use AppBundle\Entity\Form\MinimalPersonType;

class SecurityController extends Controller
{
    /**
    * @Route("/vrijwilliger_worden", name="register_user")
    */
    public function registerAction(Request $request)
    {
        $user = new Person();
        $form = $this->createForm(MinimalPersonType::class, $user);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid())
        {
            $password = $this->get("security.password_encoder")
                             ->encodePassword($user, $user->getPlainPassword());
            $user->setPassword($password);
            $em = $this->getDoctrine()->getManager();
            $em->persist($user);
            $em->flush();
            return $this->redirectToRoute("vacaturesopmaat");
        }
        return $this->render(
           "person/maakprofiel.html.twig",
           ["form" => $form->createView()] );
    }
    /**
    * @Route("/login", name = "login", options = { "i18n" = false })
    */
    public function loginAction(Request $request)
    {
        $authenticationUtils = $this->get("security.authentication_utils");
        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();
        return $this->render(
            "security/login.html.twig",
            array(
                // last username entered by the user
                "last_username" => $lastUsername,
                "error"         => $error,
            )
        );
    }


    /**
     * @Route("/loginAccountKit", name="loginAccountKit")
     */
    public function loginAccountKit(Request $request)
    {
        return $this->render("info/faq.html.twig");
    }


    /**
    * @Route("/status", name="status_testing")
    */
    public function statusAction(){
        /*if(isset($_POST["code"])){
            session_start();
            $_SESSION["code"] = $_POST["code"];
            $_SESSION["csrf_nonce"] = $_POST["csrf_nonce"];

            $ch = curl_init();
            // Set url elements
            $fb_app_id = '465871913602533';
            $ak_secret = 'eab92d7c75f08c6e95a48341c80b3ffc';
            $token = 'AA|'.$fb_app_id.'|'.$ak_secret;
            // Get access token
            $url = 'https://graph.accountkit.com/v1.0/access_token?grant_type=authorization_code&code='.$_POST["code"].'&access_token='.$token;

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_URL,$url);
            $result=curl_exec($ch);
            curl_close($ch);

            $info = json_decode($result);*/

        $em = $this->getDoctrine()->getManager();
        $userId = $this->get('security.token_storage')->getToken()->getUser()->getId();



        $myOrganisations = $em->getRepository("AppBundle:Organisation")->findBy(array('creator' => $userId));

        return $this->render("security/loginstatus.html.twig", array('myOrganisations' => $myOrganisations));
    }
}
