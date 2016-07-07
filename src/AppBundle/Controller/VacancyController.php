<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use AppBundle\Entity\Vacancy;
use AppBundle\Entity\Candidacy;
use AppBundle\Entity\Form\VacancyType;

class VacancyController extends controller
{
    /**
     * @Route("/vacature/pdf/{urlid}", name="vacancy_pdf_by_urlid")
     */
    public function createPdfAction($title)
    {
        $vacancy = $this->getVacancyRepository()->findOneByUrlid($title);
        if ($vacancy) {
            $pdf = new \FPDF_FPDF("P", "pt", "A4");
            $pdf->AddPage();
            $pdf->SetFont("Times", "B", 12);
            $pdf->Cell(0, 10, $vacancy->getTitle(), 0, 2, "C");
            $pdf->MultiCell(0, 20, "Beschrijving: \t".
                $vacancy->getDescription());
            $pdf->MultiCell(0, 20, "Organisatie: \t".
                $vacancy->getOrganisation()->getStreet(), 0, "L");
            $pdf->MultiCell(0, 20, "Locatie: \t", 0, "L");
            $pdf->Output();
            return $this->render($pdf->Output());
        } else
            throw new \Exception("De gevraagde vacature bestaat niet!");
    }


    /**
     * Controller to give the user a choice of what kind of vacancy he wants to create.
     * @Route("/vacature/start", name="start_vacancy")
     */
    public function startVacancyAction(Request $request)
    {
        $user = $this->getUser();
        $authenticationUtils = $this->get("security.authentication_utils");
        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();
        if($user){
            $organisations = $user->getOrganisations();
        }
        else
        {
            $organisations = null;
        }
        return $this->render("organisation/vrijwilliger_vinden.html.twig",
                [
                    "organisations" => $organisations,
                    "last_username" => $lastUsername,
                    "error"         => $error,
                    "path" => "start_vacancy",
                ]);
    }


    /**
     * @Security("has_role('ROLE_USER')") //TODO: apply correct role
     * @Route("/vacature/nieuw", name="create_vacancy")
     * @Route("/{organisation_urlid}/vacature/nieuw", name="create_vacancy_for_organisation")
     */
    public function createVacancyAction(Request $request, $organisation_urlid = null)
    {
        if($organisation_urlid){
            $user = $this->getUser();
            $organisation = $this->getDoctrine()->getManager()
                            ->getRepository("AppBundle:Organisation")
                            ->findOneByUrlid($urlid);
            if(!$user->getOrganisations()->contains($organisation)){
                throw $this->createAccessDeniedException("U bent geen beheerder van deze organisatie en kan er dus geen vacatures voor aanmaken.");
            }
        }

        $vacancy = new Vacancy();
        $vacancy->setStartdate(new \DateTime("today"))
            ->setEnddate(new \DateTime("today"));
        $form = $this->createForm(VacancyType::class, $vacancy);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            if (!is_null($organisation_urlid)){
                $organisation = $em->getRepository("AppBundle:Organisation")
                                    ->findOneByUrlid($organisation_urlid);
                $vacancy->setOrganisation($organisation);
            }

            if($form->get('save')->isClicked()){
                $vacancy->setPublished(Vacancy::SAVED);
            }

            $em->persist($vacancy);
            $em->flush();

            return $this->redirect($this->generateUrl("vacancy_by_urlid",
            ["urlid" => $vacancy->getUrlId() ] ));
        }
        return $this->render("vacancy/vacature_nieuw.html.twig",
            [
                "form" => $form->createView(),
                "createForm" => true,
            ]);
    }

    /**
     * @Route("/vacature/{urlid}", name="vacancy_by_urlid")
     */
    public function vacancyViewAction($urlid)
    {
        $vacancy = $this->getVacancyRepository()->findOneByUrlid($urlid);
        return $this->render("vacancy/vacature.html.twig",
            ["vacancy" => $vacancy]);
    }

    /**
     * @Security("has_role('ROLE_USER')")
     * @Route("/vacature/{urlid}/inschrijven", name="vacancy_subscribe")
     * @Route("/vacature/{urlid}/uitschrijven", name="vacancy_unsubscribe")
     */
    public function subscribeVacancyAction($urlid)
    {
        $person = $this->getUser();

        $em = $this->getDoctrine()->getManager();
        $vacancy = $em->getRepository("AppBundle:Vacancy")
            ->findOneByUrlid($urlid);

        $candidacies = $em->getRepository('AppBundle:Candidacy')
            ->findBy(array('candidate' => $person->getId(), 'vacancy' => $vacancy->getId()));

        if ($candidacies) {
            foreach ($candidacies as $candidacy) {
                $em->remove($candidacy);
                $em->flush();
            }
        } else {
            $candidacy = new Candidacy();
            $candidacy->setCandidate($person)->setVacancy($vacancy);
            $em->persist($candidacy);
            $em->flush();
        }

        return $this->redirectToRoute("vacancy_by_urlid", ["urlid" => $urlid]);
    }

    /**
     * @Security("has_role('ROLE_USER')")
     * @Route("/vacature/{urlid}/{saveaction}",
     *              name="vacancy_save",
     *              requirements={"saveaction": "save|remove"})
     */
    public function saveVacancyAction($urlid, $saveaction)
    {
        $user = $this->getUser();
        $em = $this->getDoctrine()->getManager();
        $vacancy = $em->getRepository("AppBundle:Vacancy")
            ->findOneByUrlid($urlid);
        $user->removeLikedVacancy($vacancy); // standaard unliken om geen doubles te creeren
        if ($saveaction == "save") $user->addLikedVacancy($vacancy);
        $em->persist($user);
        $em->flush();


        if (!isset($_GET['ajax'])) {
            return $this->redirectToRoute("vacancy_by_urlid", ["urlid" => $urlid]);
        } else {
            if ($saveaction == "save") {
                $arResult = array(
                    "url" => $this->generateUrl('vacancy_save', array('urlid' => $urlid, "saveaction" => "remove")),
                    "class" => "liked",
                    "text" => "Verwijder uit bewaarde vacatures",
                );
            } else {
                $arResult = array(
                    "url" => $this->generateUrl('vacancy_save', array('urlid' => $urlid, "saveaction" => "save")),
                    "class" => "notliked",
                    "text" => "Bewaar",
                );
            }
            $response = new Response();
            $response->setContent(json_encode($arResult));
            $response->headers->set('Content-Type', 'application/json');
            return $response;
        }
    }


    /**
     * @Security("has_role('ROLE_USER')")
     * @Route("/vacature/{urlid}/goedkeuren", name="vacancy_candidacies")
     */
    public function vacancyCandidacies($urlid)
    {
        $user = $this->getUser();
        $em = $this->getDoctrine()->getManager();;
        $vacancy = $em->getRepository("AppBundle:Vacancy")->findOneByUrlid($urlid);

        if($vacancy->getOrganisation()->getAdministrators()->contains($user)){
            $approved =$em->getRepository("AppBundle:Candidacy")->findBy(array('vacancy' => $vacancy->getId(),
                'state' => Candidacy::APPROVED));

            $pending = $em->getRepository("AppBundle:Candidacy")->findBy(array('vacancy' => $vacancy->getId(),
                'state' => Candidacy::PENDING));


            return $this->render("vacancy/vacature_goedkeuren.html.twig",
                ["vacancy" => $vacancy,
                 "approved" => $approved,
                 "pending" => $pending]);
        }

        throw $this->createAccessDeniedException('Je bent geen beheerder voor de organisatie die deze vacature uitschreef.  Gelieve de aanpassingen aan een beheerder door te geven.');
    }

/**
 * A list of the most recently created vacancies.
 * @param  integer $nr       The amount of vacancies desired
 * @param  string  $viewMode The viewmode for the generated output
 */
    public function listRecentVacanciesAction($nr, $viewMode = 'list')
    {
        $vacancies = $this->getDoctrine()
                        ->getRepository("AppBundle:Vacancy")
                        ->findBy(array(), array("id" => "DESC"), $nr);
        return $this->render("vacancy/vacatures_oplijsten.html.twig",
            ["vacancies" => $vacancies, "viewMode" => $viewMode]);
    }

    /**
     * @Security("has_role('ROLE_USER')")
     * @Route("/vacature/aanpassen/{urlid}", name="vacancy_edit")
     */
    public function editVacancyAction($urlid, Request $request){
        $user = $this->getUser();
        $em = $this->getDoctrine()->getManager();
        $vacancy = $em->getRepository("AppBundle:Vacancy")->findOneByurlid($urlid);

        if($vacancy->getOrganisation()->getAdministrators()->contains($user)){
            $form = $this->createForm(VacancyType::class, $vacancy);

            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $data = $form->getData();

                $vacancy->setTitle($data->getTitle());
                $vacancy->setDescription($data->getDescription());
                $vacancy->setEndDate($data->getEnddate());

                $em->flush();

                return $this->redirect($this->generateUrl("vacancy_by_urlid",
                    array("urlid" => $vacancy->getUrlId() ) ));
            }

            return $this->render("vacancy/vacature_aanpassen.html.twig",
                array("form" => $form->createView(),
                      "urlid" => $urlid) );
        }

        throw $this->createAccessDeniedException('Je bent geen beheerder voor de organisatie die deze vacature uitschreef.  Gelieve de aanpassingen aan een beheerder door te geven.');
    }

/**
 * Get vacancies matching a user profile
 * TODO: work on this
 * @param  AppBundle\Entity\Person $user the user for which the vacancies have to be retrieved
 */
    public function vacaturesOpMaatAction($user)
    {
        $query = $this->get("ElasticsearchQuery");
        $params = [
            'index' => $query->getIndex(),
            'type' => 'vacancy',
        ];

        $results = $query->search($params);

        return $this->render("vacancy/vacature_tab.html.twig", ['vacancies' => $query->getResults(), 'title' => 'Vacatures op maat']);//TODO retrieve and add matching vacancies here
    }

/**
 * Get all saved vacancies for a user
 * @param  AppBundle\Entity\Person $user the user for which the vacancies have to be retrieved
 */
    public function listSavedVacanciesAction($user)
    {
        return $this->render("vacancy/vacatures_oplijsten.html.twig",
            ["vacancies" => $user->getLikedVacancies(), "viewMode" => 'tile']);
    }


/**
 * Create a list of all vacancies that are currently open, for a given organisation
 * @param integer $id an organisation id
 */
    public function ListOrganisationVacanciesAction($id, $status = Vacancy::OPEN)
    {
        $vacancy = $this->getVacancyRepository();
        $query = $vacancy->createQueryBuilder("v")
            ->where("v.organisation = :id and v.published = :status")
            ->setParameter('id', $id)
            ->setParameter('status', $status)
            ->getQuery();

        $vacancies = $query->getResult();

        return $this->render("vacancy/vacatures_oplijsten.html.twig",
                [
                    "vacancies" => $vacancies,
                    "viewMode" => "tile",
                ]);
    }

/**
 * Delete or restore a vacancy
 * @Route("/vacature/{urlid}/delete", name="delete_vacancy", defaults={ "deleted" = 4 })
 * @Route("/vacature/{urlid}/restore", name="restore_vacancy", defaults={ "deleted" = 1 })
 * @param  AppBundle\Entity\Vacancy $vacancy the vacancy to be deleted or restored
 */
    public function changeVacancyPublishedStatusAction($urlid, $deleted)
    {
        $user = $this->getUser();
        $em = $this->getDoctrine()->getManager();
        $vacancy = $em->getRepository("AppBundle:Vacancy")
            ->findOneByUrlid($urlid);
        if($vacancy->getOrganisation()->getAdministrators()->contains($user)){
            $vacancy->setPublished($deleted);
            $em->persist($vacancy);
            $em->flush();
        }

        return $this->redirectToRoute('vacancy_by_urlid', array('urlid' => $urlid));
    }

    private function getVacancyRepository(){
        return $this->getDoctrine()->getManager()->getRepository("AppBundle:Vacancy");
    }
}
