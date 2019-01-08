<?php

namespace EShopBundle\Controller;

use EShopBundle\Entity\Order;
use EShopBundle\Entity\Product;
use EShopBundle\Entity\ProductCart;
use EShopBundle\Entity\User;
use EShopBundle\Form\ProductType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Controller used to manage the application security.
 */
class ProductController extends Controller
{
    /**
     * @Security("is_granted('IS_AUTHENTICATED_FULLY')")
     * @Route("/products/create", name="product_create")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function createProduct(Request $request)
    {

        $product = new Product();

        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user = $this->getUser();
            /** @var UploadedFile $file */
            $file = $form->getData()->getPictureUrl();
            $fileName = md5(uniqid()) . '.' . $file->guessExtension();

            try {
                $file->move($this->getParameter('product_directory'),
                    $fileName);
            } catch (FileException $ex) {

            }

            $product->setPictureUrl($fileName);
            $product->setAuthor($user);
            $product->setViewCount(0);

            $em = $this->getDoctrine()->getManager();
            $em->persist($product);
            $em->flush();

            return $this->redirect("/");
        }

        return $this->render('product/create.html.twig', ['form' => $form->createView()]);
    }

    /**
     * @Route("/products/{id}", name="product_details")
     * @param $id
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function detailsProduct($id)
    {
        $repo = $this
            ->getDoctrine()
            ->getRepository(Product::class);

        $product = $repo->find($id);

        $product->setViewCount($product->getViewCount() + 1);

        $em = $this->getDoctrine()->getManager();
        $em->persist($product);
        $em->flush();

        return $this->render('product/details.html.twig', ['product' => $product]);
    }

    /**
     * @Security("is_granted('IS_AUTHENTICATED_FULLY')")
     * @Route("/products/edit/{id}", name="product_edit")
     * @param Request $request
     * @param $id
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function editProduct(Request $request, $id)
    {
        $repo = $this
            ->getDoctrine()
            ->getRepository(Product::class);

        $product = $repo->find($id);

        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            /** @var UploadedFile $file */
            $file = $form->getData()->getPictureUrl();
            $fileName = md5(uniqid()) . '.' . $file->guessExtension();

            try {
                $file->move($this->getParameter('product_directory'),
                    $fileName);
            } catch (FileException $ex) {

            }

            $product->setPictureUrl($fileName);
            $em->persist($product);
            $em->flush();

            return $this->redirectToRoute("product_details", ["id" => $id]);
        }

        return $this->render('product/edit.html.twig', ['product' => $product, "form" => $form->createView()]);
    }


    /**
     * @Security("is_granted('IS_AUTHENTICATED_FULLY')")
     * @Route("/products/delete/{id}", name="product_delete")
     * @param Request $request
     * @param $id
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function deleteProduct(Request $request, $id)
    {
        $repo = $this
            ->getDoctrine()
            ->getRepository(Product::class);

        $product = $repo->find($id);

        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($product);
            $em->flush();

            return $this->redirectToRoute("home_index");
        }

        return $this->render('product/delete.html.twig', ['product' => $product, "form" => $form->createView()]);
    }
}
