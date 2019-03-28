.checkout
=========

1. A Symfony project created on November 17, 2018, 2:24 pm.
2. Description for my E-shop
3. Everybody can view the listed products.
4. First registered users gets ROLE_ADMIN and he can add items and edit or delete all the listed items.
5. Every user registered after this gets ROLE_USER.
6. Registered users can add new items with Title, Description, Price and Picture.
7. The pictures are stored locally with generated unique name and only the name is stored in the database.
8. User can edit his profile and can add a profile picture or he can be without one. Profile pics are also stored locally.
9. If user add a profile picture on the navbar will be shown thumbnail of his picture which can be clicked and lead to his profile
10. All users can Deposit money which are used to buy items. Deposit is validated and only possitive amounts are requred.
11. All users can Withdraw money from their account. The amount is validated and if you try to withdraw more than you have you will get an error.
12. You can add items to cart and when you go to your cart you can buy individual items ot buy all items.
13. There are also validations when you try to buy items. If you don't have enough money you can't buy the item.
14. In Cart menu you also see what your balance is, what's the total cost of selected items and how much will be your new balance when you checkout.
15. Every error is displayed as red flash message, that you can close.(negative deposit,wrong withdraw amount,insofficient amount etc.)
16. Every successful operation is displayed as green flash message (successfull deposit,withdraw, and every item you buy is  as flash message displayed).
17. When in your profile you can see the total amount of money you spend every item's name, price and the exact time you bought it.
