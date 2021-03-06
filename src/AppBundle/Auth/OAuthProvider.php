<?php
namespace AppBundle\Auth;
 
use HWI\Bundle\OAuthBundle\Security\Core\User\OAuthUserProvider;
use HWI\Bundle\OAuthBundle\OAuth\Response\UserResponseInterface;
use AppBundle\Entity\Person;
 
class OAuthProvider extends OAuthUserProvider
{
    protected $session, $doctrine, $admins;
 
    public function __construct($session, $doctrine, $service_container)
    {
        $this->session = $session;
        $this->doctrine = $doctrine;
        $this->container = $service_container;
    }
 

    public function loadUserByOAuthUserResponse(UserResponseInterface $response)
    {
        //Data from response 
        $email = $response->getEmail();
        $firstname = $response->getFirstName();
        $lastname = $response->getLastName(); 

        $nickname = $firstname . $lastname;
 
        //Check if this user already exists in our app DB
        $qb = $this->doctrine->getManager()->createQueryBuilder();
        $qb->select('u')
            ->from('AppBundle:Person', 'u')
            ->where('u.email = :gmail')
            ->setParameter('gmail', $email)
            ->setMaxResults(1);
        $result = $qb->getQuery()->getResult();
 
        //add to database if doesn't exists
        if (!count($result)) {
            $person = new Person(); 
            $person->setFirstname($firstname);
            $person->setLastname($lastname);
            $person->setUsername($nickname);
            $person->setEmail($email);
            //$user->setRoles('ROLE_USER');
 
            //Set some wild random pass since its irrelevant, this is Google login
            $factory = $this->container->get('security.encoder_factory');
            $encoder = $factory->getEncoder($person);
            $password = $encoder->encodePassword(md5(uniqid()), $person->getSalt());
            $person->setPassword($password);
 
            $em = $this->doctrine->getManager();
            $em->persist($person);
            $em->flush();
        } else {
            $person = $result[0];  
        }
 
        //set id
        $this->session->set('id', $person->getId());
        
        return $person;
    }
}