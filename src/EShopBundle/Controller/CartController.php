<?php

namespace EShopBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use EShopBundle\Entity\Order;
use EShopBundle\Entity\Product;
use EShopBundle\Entity\ProductCart;
use EShopBundle\Entity\User;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\Routing\Annotation\Route;

class CartController extends Controller
{
    /**
     * @Security("is_granted('IS_AUTHENTICATED_FULLY')")
     * @Route("/cart/products", name="cart_products")
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function cartProducts()
    {
        $userId = $this->getUser()->getId();
        $user = $this->getUser();
        $repo = $this->getDoctrine()->getRepository(ProductCart::class);
        $cartProductsFromDB = $repo->findBy(["userId" => $userId, "isDeleted" => false]);

        $totalSum = 0;
        $cartProducts = [];
        $balance = $user->getBalance();

        for ($i = 0; $i < count($cartProductsFromDB); $i++) {
            $product = $cartProductsFromDB[$i]->getProduct();
            $cartProducts[] = $product;
            $totalSum += $product->getPrice();
        }
        $moneyLeft=$balance-$totalSum;
        return $this->render('user\cartProducts.html.twig', ['moneyLeft'=>$moneyLeft,"balance"=>"$balance]",'products' => $cartProducts, 'total' => $totalSum]);
    }

    /**
     * @Security("is_granted('IS_AUTHENTICATED_FULLY')")
     * @Route("/cart/products/add/{id}", name="add_to_cart")
     * @param Product $product
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function addProductInCart(Product $product)
    {
        $userId = $this->getUser()->getId();

        $user = $this
            ->getDoctrine()
            ->getRepository(User::class)
            ->find($userId);

        $productId = $product->getId();

        if ($user->isAuthor($product)) {
            return $this->redirectToRoute('product_details', ['id' => $productId]);
        }

        $productCartRepo = $this->getDoctrine()->getRepository(ProductCart::class);
        $productCart = $productCartRepo->findOneBy(['productId' => $productId, 'userId' => $userId]);

        if ($productCart == null) {
            $productCart = new ProductCart();

            $productCart->setUser($user);
            $productCart->setProduct($product);
        }

        $productCart->setIsDeleted(false);

        $em = $this->getDoctrine()->getManager();
        $em->persist($productCart);
        $em->flush();

        return $this->redirectToRoute('cart_products');
    }

    /**
     * @Security("is_granted('IS_AUTHENTICATED_FULLY')")
     * @Route("/cart/products/remove/{id}", name="product_cart_remove")
     * @param $id
     * @return \Symfony\Component\HttpFoundation\Response
     */

    public function removeProductFromCart($id)
    {
        $product = $this
            ->getDoctrine()
            ->getRepository(Product::class)
            ->find($id);

        $user = $this->getUser();

        $productCart = $this
            ->getDoctrine()
            ->getRepository(ProductCart::class)
            ->findOneBy(['product' => $product, 'user' => $user]);

        if ($productCart == null) {
            return $this->redirectToRoute("cart_products");
        }

        $productCart->setIsDeleted(true);

        $em = $this->getDoctrine()->getManager();
        $em->persist($productCart);
        $em->flush();

        return $this->redirectToRoute('cart_products');
    }

    /**
     * @Security("is_granted('IS_AUTHENTICATED_FULLY')")
     * @Route("/cart/products/buy/{id}", name="product_cart_buy")
     * @param Product $product
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Exception
     */
    public function buyProductFromCart(Product $product)
    {
        $user = $this->getUser();

        $productCart = $this
            ->getDoctrine()
            ->getRepository(ProductCart::class)
            ->findOneBy(['product' => $product, 'user' => $user]);

        if ($productCart != null) {
            $order = new Order();

            $order->setProduct($product);
            $order->setUser($user);

            $productCart->setIsDeleted(true);
            $balance = $user->getBalance();
            $productCost=$product->getPrice();
            $newBalance = $balance -$productCost;
            if($newBalance<0){
                throw new \Exception("not enough money!");
            }
            $user->setBalance($newBalance);
            $em = $this->getDoctrine()->getManager();
            $em->persist($user);
            $em->flush();

            $em = $this->getDoctrine()->getManager();
            $em->persist($order);
            $em->persist($productCart);
            $em->flush();
        }

        return $this->redirectToRoute('cart_products');
    }

    /**
     * @Security("is_granted('IS_AUTHENTICATED_FULLY')")
     * @Route("/cart/products/buyAll", name="all_product_cart_buy")
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Exception
     */
    public function buyAllProductFromCart()
    {
        $user = $this->getUser();

        $cartProducts = $this
            ->getDoctrine()
            ->getRepository(ProductCart::class)
            ->findBy(['user' => $user, 'isDeleted' => false]);

        $em = $this->getDoctrine()->getManager();

        foreach ($cartProducts as $productCart) {
            $order = new Order();

            $order->setProduct($productCart->getProduct());
            $order->setUser($user);
            $productCart->setIsDeleted(true);
            $balance = $user->getBalance();
            $productCost=$order->getProduct()->getPrice();

            $newBalance = $balance - floatval($productCost);
            if($newBalance<0){
                throw new \Exception("not enough money!");
            }
            $user->setBalance($newBalance);
            $em = $this->getDoctrine()->getManager();
            $em->persist($user);
            $em->flush();

            $em->persist($order);
            $em->persist($productCart);
        }

        $em->flush();

        return $this->redirectToRoute('cart_products');
    }
}
