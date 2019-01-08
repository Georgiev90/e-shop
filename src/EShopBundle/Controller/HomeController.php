<?php

namespace EShopBundle\Controller;

use EShopBundle\Entity\Product;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Controller used to manage the application security.
 */
class HomeController extends Controller
{
    /**
     * @Route("/", name="home_index")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(Request $request)
    {
       if ($user = $this->getUser()){
           $em = $this->getDoctrine()->getManager();
           $products = $em
               ->getRepository(Product::class)
               ->createQueryBuilder('e')
               ->addOrderBy('e.dateAdded', 'DESC')
               ->getQuery()
               ->execute();
        $pic=$user->getPicture();
           return $this->render('home/index.html.twig', ["userpic"=>"$pic","products" => $products]);
       }
        $em = $this->getDoctrine()->getManager();

        $products = $em
            ->getRepository(Product::class)
            ->createQueryBuilder('e')
            ->addOrderBy('e.dateAdded', 'DESC')
            ->getQuery()
            ->execute();

        return $this->render('home/index.html.twig', ["products" => $products]);
    }
}
