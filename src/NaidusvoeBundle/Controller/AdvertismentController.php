<?php

namespace NaidusvoeBundle\Controller;

use Doctrine\ORM\EntityManager;
use NaidusvoeBundle\Entity\Advertisment;
use NaidusvoeBundle\Entity\Attachment;
use NaidusvoeBundle\Entity\Favorites;
use NaidusvoeBundle\Entity\Functions;
use NaidusvoeBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class AdvertismentController extends Controller
{
    /**
     * @return JsonResponse
     */
    public function getAdvDetailsAction() {
        /** @var EntityManager $em */
        $em = $this->getDoctrine()->getManager();
        /** @var User $user */
        $user = $this->getUser();
        /** @var array $categories */
        $categories = Functions::arrayToJson($em->getRepository('NaidusvoeBundle:AdvertismentCategory')->findAll());
        $priceTypes = Functions::arrayToJson($em->getRepository('NaidusvoeBundle:PriceType')->findAll());
        $advTypes = Functions::arrayToJson($em->getRepository('NaidusvoeBundle:AdvertismentType')->findAll());
        $info = array(
            'advTypes' => $advTypes,
            'categories' => $categories,
            'priceTypes' => $priceTypes,
            'contactPerson' => $user->getName() . ' ' . $user->getSurname(),
            'email' => $user->getEmail(),
            'telephoneNumber' => $user->getTelephoneNumber(),
            'skype' => $user->getSkype(),
        );

        return new JsonResponse($info);
    }

    /**
     * @Security("has_role('ROLE_USER')")
     * @param Request $request
     * @return JsonResponse
     */
    public function addNewAdvAction(Request $request) {
        $data = json_decode($request->getContent(), true);
        $data = (object) $data['adv'];
        /** @var EntityManager $em */
        $em = $this->getDoctrine()->getManager();
        /** @var User $user */
        $user = $this->getUser();
        $adv = Advertisment::addNewAdv($em, $data, $user);
        $images = Attachment::uploadImages($em, $data->photos, $adv->getId());
        return new JsonResponse(($adv) ? $adv->getInArray() : null);
    }

    /**
     * @param int $adv_id
     * @return JsonResponse
     */
    public function getAdvAction($adv_id) {
        /** @var Advertisment $adv */
        $adv = $this->getDoctrine()->getManager()->find('NaidusvoeBundle:Advertisment', $adv_id);
        return new JsonResponse($adv->getInArray());
    }

    /**
     * @param int $page_id
     * @return JsonResponse
     */
    public function getAdvsTradeAction($page_id = 1) {
        $offset = ($page_id - 1) * 10;
        $limit = $page_id * 10;

        $advs = $this->getDoctrine()->getRepository('NaidusvoeBundle:Advertisment')
            ->findBy(array('typeID' => 1), array('date' => 'DESC'), $limit, $offset);

        /** @var EntityManager $em */
        $em = $this->getDoctrine()->getManager();
        $query = $em->createQuery(
            'SELECT COUNT(a.id) ' .
            'FROM NaidusvoeBundle:Advertisment a ' .
            'WHERE a.typeID = :param')
            ->setParameter('param', 1);
        $count = $query->getSingleScalarResult();
        $advs = Functions::arrayToJson($advs);
        return new JsonResponse(array('advs' => $advs, 'pageCount' => intval($count / 10)));
    }

    /**
     * @param int $page_id
     * @return JsonResponse
     */
    public function getAdvsFoundAction($page_id) {
        $offset = ($page_id - 1) * 10;
        $limit = $page_id * 10;
        $advs = $this->getDoctrine()->getRepository('NaidusvoeBundle:Advertisment')
            ->findBy(array('typeID' => 2), array('date' => 'DESC'), $limit, $offset);

        /** @var EntityManager $em */
        $em = $this->getDoctrine()->getManager();
        $query = $em->createQuery(
            'SELECT COUNT(a.id) ' .
            'FROM NaidusvoeBundle:Advertisment a ' .
            'WHERE a.typeID = :param')
            ->setParameter('param', 2);
        $count = $query->getSingleScalarResult();
        $advs = Functions::arrayToJson($advs);
        return new JsonResponse(array('advs' => $advs, 'pageCount' => intval($count / 10)));
    }

    /**
     * @param int $page_id
     * @return JsonResponse
     */
    public function getAdvsGiftAction($page_id) {
        $offset = ($page_id - 1) * 10;
        $limit = $page_id * 10;
        $advs = $this->getDoctrine()->getRepository('NaidusvoeBundle:Advertisment')
            ->findBy(array('typeID' => 3), array('date' => 'DESC'), $limit, $offset);

        /** @var EntityManager $em */
        $em = $this->getDoctrine()->getManager();
        $query = $em->createQuery(
            'SELECT COUNT(a.id) ' .
            'FROM NaidusvoeBundle:Advertisment a ' .
            'WHERE a.typeID = :param')
            ->setParameter('param', 3);
        $count = $query->getSingleScalarResult();
        $advs = Functions::arrayToJson($advs);
        return new JsonResponse(array('advs' => $advs, 'pageCount' => intval($count / 10)));
    }

    /**
     * @param int $adv_id
     * @return JsonResponse
     */
    public function addToFavAction($adv_id) {
        /** @var EntityManager $em */
        $em = $this->getDoctrine()->getManager();
        try {
            Advertisment::addToFav($em, $this->getUser()->getId(), $adv_id);
        } catch (\Exception $ex) {
            $from = "Class: Advertisment, function: addToFav";
            $this->get('error_logger')->registerException($ex, $from);
            return new JsonResponse(-1);
        }
        return new JsonResponse(1);
    }
}