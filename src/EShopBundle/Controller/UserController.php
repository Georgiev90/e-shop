<?php

namespace EShopBundle\Controller;


use EShopBundle\Entity\Order;
use EShopBundle\Entity\Product;
use EShopBundle\Entity\Role;
use EShopBundle\Entity\Transaction;
use EShopBundle\Entity\User;
use EShopBundle\Form\TransactionType;
use EShopBundle\Form\UserType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;


/**
 * Controller used to manage the application security.
 */
class UserController extends Controller
{
    /**
     * @Route("/user/register", name="user_register")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function registerAction(Request $request)
    {
        // 1) build the form
        $user = new User();
        $form = $this->createForm(UserType::class, $user);

        // 2) handle the submit (will only happen on POST)
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // 3) Encode the password (you could also do this via Doctrine listener)
            $password = $this->get("security.password_encoder")
                ->encodePassword($user, $user->getPassword());
            $user->setPassword($password);

            $roleRepository = $this->getDoctrine()->getRepository(Role::class);
            $userRepository = $this->getDoctrine()->getRepository(User::class);

            $usersFromDB = $userRepository->findAll();

            $em = $this->getDoctrine()->getManager();


            // First registered user is ADMIN
            if ($usersFromDB == null) {
                $role = new Role();
                $role->setName('ROLE_ADMIN');
                $em->persist($role);

                $user->addRole($role);

                // 4) save the User!
                $em->persist($user);
                $em->flush();

                return $this->redirectToRoute('security_login');
            }

            $userRole = $roleRepository->findOneBy(['name' => 'ROLE_USER']);

            if ($userRole == null) {
                $userRole = new Role();
                $userRole->setName('ROLE_USER');
                $em->persist($userRole);
            }

            $user->addRole($userRole);

            // 4) save the User!
            $user->setBalance(0);
            $em->persist($user);
            $em->flush();

            return $this->redirectToRoute('security_login');
        }

        return $this->render('user/register.html.twig', array('form' => $form->createView()));
    }

    /**
     * @Security("is_granted('IS_AUTHENTICATED_FULLY')")
     * @Route("/user/profile", name="user_profile")
     * @param AuthorizationCheckerInterface $authChecker
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function profileAction(AuthorizationCheckerInterface $authChecker)
    {
//        if ($authChecker->isGranted('ROLE_USER')) {
//            throw new AccessDeniedException();
//        }

        $user = $this->getUser();

        $userOrders = $this
            ->getDoctrine()
            ->getRepository(Order::class)
            ->findBy(['user' => $user]);

        $totalMoney = 0;

        foreach ($userOrders as $order) {
            $totalMoney += $order->getProduct()->getPrice();
        }

        return $this->render("user/profile.html.twig", ['user' => $user, 'orders' => $userOrders, 'total' => $totalMoney]);
    }

    /**
     * @Security("is_granted('IS_AUTHENTICATED_FULLY')")
     * @Route("/user/profile_edit", name="profile_edit")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function profileEdit(Request $request)
    {
        $userId = $this->getUser()->getId();
        $current = $this->getUser();
        $user = $repo = $this->getDoctrine()
            ->getRepository(User::class)
            ->find($userId);

        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $em = $this->getDoctrine()->getManager();
            $password = $this->get("security.password_encoder")
                ->encodePassword($user, $user->getPassword());
            $user->setPassword($password);
            /** @var UploadedFile $file */
            $file = $form->getData()->getPicture();
            $fileName = md5(uniqid()) . '.' . $file->guessExtension();

            try {
                $file->move($this->getParameter('product_directory'),
                    $fileName);
            } catch (FileException $ex) {

            }

            $user->setPicture($fileName);
            $em->persist($user);
            $em->flush();

            return $this->render('user/edit_profile.html.twig', ['user' => $current, "form" => $form->createView()]);
        }

        return $this->render('user/edit_profile.html.twig', ['user' => $user, "form" => $form->createView()]);
    }

    /**
     * @Security("is_granted('IS_AUTHENTICATED_FULLY')")
     * @Route("/user/myProducts", name="my_created_products")
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function listCreatedProducts()
    {
        $repo = $this
            ->getDoctrine()
            ->getRepository(Product::class);

        $products = $repo->findBy(['authorId' => $this->getUser()]);

        return $this->render('user/myProducts.html.twig', ['products' => $products]);
    }

    /**
     * @Security("is_granted('IS_AUTHENTICATED_FULLY')")
     * @Route("/user/deposit", name="user_deposit")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function deposit(Request $request)
    {
        $transaction = new Transaction();
        $user = $this->getUser();

        $currentUser = $this
            ->getDoctrine()
            ->getRepository(User::class)
            ->find($user->getId());


        $balance = $currentUser->getBalance();


        $form = $this->createForm(TransactionType::class, $transaction);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $deposit = $transaction->getAmount();
            if ($deposit >= 0) {

                $newBalance = $balance + floatval($deposit);
                $user->setBalance($newBalance);
                $em = $this->getDoctrine()->getManager();
                $em->persist($currentUser);
                $em->flush();
                $this->addFlash('success', "$$deposit Successfully added to your account");
            } else {
                $this->addFlash('message', "Transaction with negative numbers is not allowed!");
            }
        }
        return $this->render('user/deposit.html.twig', ['user' => $currentUser, 'user[balance]' => $balance]);
    }

    /**
     * @Security("is_granted('IS_AUTHENTICATED_FULLY')")
     * @Route("/user/withdraw", name="user_withdraw")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Exception
     */
    public function withdraw(Request $request)
    {
        $transaction = new Transaction();
        $user = $this->getUser();

        $currentUser = $this
            ->getDoctrine()
            ->getRepository(User::class)
            ->find($user->getId());

        $balance = $currentUser->getBalance();

        $form = $this->createForm(TransactionType::class, $transaction);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $withdraw = $transaction->getAmount();
            if ($withdraw > 0 && $withdraw < $balance) {

                $newBalance = $balance - floatval($withdraw);
                $user->setBalance($newBalance);
                $em = $this->getDoctrine()->getManager();
                $em->persist($currentUser);
                $em->flush();
                $this->addFlash('success', "$$withdraw Successfully withdrawn from your account");
            } else if ($withdraw > $balance) {
                $this->addFlash('message', "You don't have enough money!");
            } else {
                $this->addFlash('message', "Transaction with negative amounts is not allowed!");
            }

        }
        return $this->render('user/withdraw.html.twig', ['user' => $currentUser, 'user[balance]' => $balance]);
    }
}
