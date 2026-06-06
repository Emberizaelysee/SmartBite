-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Jun 06, 2026 at 09:50 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `Temp`
--

-- --------------------------------------------------------

--
-- Table structure for table `category`
--

CREATE TABLE `category` (
  `IdCategory` int(11) NOT NULL,
  `CategoryName` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `category`
--

INSERT INTO `category` (`IdCategory`, `CategoryName`) VALUES
(1, ' PIZZAS  '),
(2, 'BURGERS'),
(4, 'PASTAS'),
(5, 'SALADS'),
(6, 'DESSERTS'),
(7, 'DRINKS');

-- --------------------------------------------------------

--
-- Table structure for table `menu`
--

CREATE TABLE `menu` (
  `IdMenu` int(11) NOT NULL,
  `ItemName` varchar(255) NOT NULL,
  `ItemDescription` varchar(255) NOT NULL,
  `ItemIngredients` varchar(255) DEFAULT NULL,
  `ItemPrice` decimal(7,2) NOT NULL,
  `ImageURL` varchar(255) NOT NULL,
  `IdCategory` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `menu`
--

INSERT INTO `menu` (`IdMenu`, `ItemName`, `ItemDescription`, `ItemIngredients`, `ItemPrice`, `ImageURL`, `IdCategory`) VALUES
(1, 'ALFREDO PASTA', 'Alfredo Pasta\r\nChicken Alfredo\r\nFettuccine Alfredo\r\nCreamy Alfredo\r\nWhite Sauce Pasta\r\nAlfredo with Chicken\r\nAlfredo with Mushrooms\r\nAlfredo', 'Unsalted butter\r\nHeavy cream\r\nWhole milk \r\nFresh garlic\r\nParmesan cheese \r\nRomano cheese \r\nCream cheese \r\nSalt\r\nBlack pepper\r\nNutmeg \r\nFresh parsley', 5.00, 'img/menu-img/alfredo pasta.webp', 4),
(2, 'BOLOGNESE PASTA', 'Bolognese Pasta\r\nSpaghetti Bolognese\r\nMeat Sauce Pasta\r\nBeef Bolognese\r\nClassic Bolognese\r\nPasta with Meat Sauce', 'Ground beef\r\nGround pork \r\nPancetta \r\nOnion\r\nCarrot\r\nCelery\r\nGarlic\r\nOlive oil\r\nButter\r\nTomato paste\r\ntomato sauce \r\nWhole milk or cream\r\nBeef or chicken broth \r\nBay leaves\r\noregano\r\nNutmeg \r\nSalt\r\nBlack pepper', 5.50, 'img/menu-img/bolognese pasta.avif', 4),
(3, 'CARBONARA PASTA', 'Carbonara Pasta\r\nSpaghetti Carbonara\r\nClassic Carbonara\r\nCreamy Carbonara\r\nCarbonara with Bacon\r\nItalian Carbonara', 'Eggs (whole eggs)\r\nPecorino Romano cheese\r\nParmesan cheese (with Pecorino)\r\nGuanciale\r\nPancetta \r\nBacon \r\nBlack pepper \r\nSalt \r\nPasta cooking water (to emulsify the sauce)', 5.00, 'img/menu-img/carbonara pasta.webp', 4),
(4, 'PESTO PASTA', 'Pesto Pasta\r\nBasil Pesto Pasta\r\nGreen Pesto Pasta\r\nPesto Penne\r\nPesto with Chicken\r\nVegetarian Pesto Pasta', 'Fresh basil leaves\r\nGarlic\r\nPine nuts\r\nParmesan cheese \r\nPecorino cheese (mixed with Parmesan)\r\nExtra virgin olive oil\r\nSalt\r\nBlack pepper \r\nLemon juice ', 5.00, 'img/menu-img/pesto pasta.avif', 4),
(5, 'CHEESE BURGER', 'Cheeseburger\r\nClassic Cheeseburger\r\nBeef Cheeseburger\r\nDouble Cheeseburger\r\nAmerican Cheeseburger\r\nCheddar Cheeseburger\r\nGrilled Cheeseburger', 'Ground beef\r\nSalt\r\nBlack pepper\r\nGarlic powder\r\nOnion powder\r\nWorcestershire sauce\r\nBurger buns\r\nCheddar cheese slices \r\nLettuce\r\nTomato slices\r\nOnion slices\r\nPickles\r\nKetchup\r\nMustard\r\nMayonnaise\r\nButter (for toasting buns)\r\nChicken burger', 5.20, 'img/menu-img/cheese bur.jpg', 2),
(6, 'CHICKEN BURGER', 'Chicken Burger\r\nCrispy Chicken Burger\r\nGrilled Chicken Burger\r\nSpicy Chicken Burger\r\nChicken Sandwich\r\nChicken Deluxe Burger\r\nChicken Fillet Burger', 'Burger buns\r\nLettuce\r\nTomato slices\r\nPickles\r\nCheese slices \r\nMayonnaise\r\nKetchup\r\nGarlic sauce \r\nChicken breast or ground chicken\r\nSalt\r\nBlack pepper\r\nGarlic powder\r\nOnion powder\r\nPaprika\r\nFlour or breadcrumbs \r\nButtermilk \r\nOil ', 5.50, 'img/menu-img/chicken bur.webp', 2),
(7, 'FISH BURGER', 'Fish Burger\r\nCrispy Fish Burger\r\nFish Fillet Burger\r\nFried Fish Sandwich\r\nGrilled Fish Burger\r\nSeafood Burger\r\nFish Deluxe Burger', 'White fish fillet \r\nSalt\r\nBlack pepper\r\nLemon juice\r\nGarlic\r\nFlour\r\nBreadcrumbs \r\nOil \r\nBurger buns\r\nLettuce\r\nTomato slices\r\nPickles\r\nCheese slices (optional)\r\nTartar sauce (mayonnaise + pickles + lemon juice + capers)\r\nMayonnaise\r\nKetchup (optional)', 5.50, 'img/menu-img/fish bur.webp', 2),
(8, 'BBQ BURGER', 'BBQ Burger\r\nBBQ Beef Burger\r\nBBQ Chicken Burger\r\nSmoky BBQ Burger\r\nBBQ Bacon Burger\r\nGrilled BBQ Burger\r\nBarbecue Sauce Burger', 'Ground beef\r\nSalt\r\nBlack pepper\r\nGarlic powder\r\nOnion powder\r\nWorcestershire sauce\r\nSmoked paprika \r\nBurger buns\r\nsmoked cheese\r\nLettuce\r\nTomato slices\r\nOnion rings (fried)\r\nPickles\r\nBBQ sauce\r\nMayonnaise \r\nMustard \r\nBacon strips\r\n', 5.70, 'img/menu-img/BBQ bur.webp', 2),
(9, 'MARGHERITA PIZZA', 'Margherita Pizza\r\nClassic Margherita\r\nTomato Basil Pizza\r\nMozzarella Pizza\r\nItalian Margherita\r\nFresh Basil Pizza\r\nSimple Cheese Pizza', 'Pizza dough\r\nTomato sauce (crushed tomatoes, olive oil, salt)\r\nFresh mozzarella cheese\r\nFresh basil leaves\r\nOlive oil\r\nSalt ', 5.00, 'img/menu-img/margherita pizza.jpg', 1),
(10, 'FOUR CHEESE PIZZA', 'Four Cheese Pizza\r\nQuattro Formaggi\r\nCheese Lovers Pizza\r\nMixed Cheese Pizza\r\nMozzarella Cheddar Parmesan Pizza\r\nCreamy Cheese Pizza\r\nItalian Four Cheese', 'Pizza dough\r\nWhite sauce \r\nMozzarella cheese\r\nParmesan cheese\r\nGorgonzola cheese \r\nFontina / Cheddar / Emmental \r\nOlive oil\r\nOregano ', 5.35, 'img/menu-img/cheese pizza.jpg', 1),
(11, 'HAWAIIN PIZZA', 'Hawaiian Pizza\r\nPineapple Pizza\r\nHam and Pineapple Pizza\r\nSweet and Savory Pizza\r\nHawaiian Style Pizza\r\nChicken Hawaiian Pizza\r\nTropical Pizza', 'Pizza dough\r\nTomato sauce\r\nMozzarella cheese\r\nsmoked ham\r\nPineapple chunks\r\nOlive oil ', 5.75, 'img/menu-img/hawaiin pizza.webp', 1),
(12, 'VEGGIE PIZZA', 'Veggie Pizza\r\nVegetarian Pizza\r\nGarden Pizza\r\nMixed Vegetable Pizza\r\nHealthy Veggie Pizza\r\nMushroom Veggie Pizza\r\nGreen Pizza', 'Pizza dough\r\nTomato sauce\r\nMozzarella cheese\r\nBell peppers\r\nMushrooms\r\nOnions\r\nBlack olives\r\nTomatoes\r\nCorn \r\nOlive oil\r\nOregano ', 4.70, 'img/menu-img/veggie pizza.jpeg', 1),
(13, 'TUNA SALAD', 'Tuna Salad\r\nClassic Tuna Salad\r\nHealthy Tuna Salad\r\nTuna Mayo Salad\r\nProtein Tuna Salad\r\nGreen Tuna Salad\r\nFresh Tuna Salad', 'Tuna \r\nMayonnaise\r\nLettuce \r\nOnion ( white)\r\nCelery \r\nLemon juice\r\nSalt & black pepper\r\nOlive oil \r\n pickles ', 5.50, 'img/menu-img/tunaa salad.jpg', 5),
(14, 'CHICKEN CEASER SALAD', 'Chicken Caesar Salad\r\nCaesar Salad\r\nGrilled Chicken Caesar\r\nCrispy Chicken Caesar\r\nClassic Caesar Salad\r\nCaesar Salad with Chicken\r\nParmesan Caesar Salad', 'Romaine lettuce\r\nGrilled chicken breast\r\nCroutons\r\nParmesan cheese\r\nCaesar dressing (egg yolk, garlic, anchovy, lemon juice, olive oil, mustard, Worcestershire sauce)\r\nBlack pepper', 5.80, 'img/menu-img/chicken ceaser salad.jpg', 5),
(15, 'GREEK SALAD', 'Greek Salad\r\nHoriatiki Salad\r\nTraditional Greek Salad\r\nMediterranean Salad\r\nFeta Cheese Salad\r\nOlive Salad\r\nFresh Greek Salad', 'Tomatoes\r\nCucumbers\r\nRed onions\r\nGreen bell peppers\r\nKalamata olives\r\nFeta cheese\r\nOlive oil\r\nOregano\r\nSalt\r\nLettuce', 5.15, 'img/menu-img/greek salad.jpg', 5),
(16, 'TACO SALAD', 'Taco Salad\r\nMexican Salad\r\nBeef Taco Salad\r\nChicken Taco Salad\r\nCrispy Taco Salad\r\nTex-Mex Salad\r\nLoaded Taco Salad', 'Lettuce \r\nGround beef (seasoned with taco spices)\r\nCheddar cheese\r\nTomatoes\r\nBlack beans\r\nCorn \r\nSalsa\r\nSour cream\r\nTortilla chips \r\nguacamole ', 5.55, 'img/menu-img/taco salad.webp', 5),
(21, 'STRAWBERRY SMOOTHIE', 'Strawberry flavor\r\nSweet drink\r\nCreamy smoothie\r\nFresh fruit\r\nCold beverage\r\nSummer drink\r\nMilk-based drink\r\nFruity smoothie', 'Strawberries\r\nBanana\r\nMilk\r\nYogurt\r\nHoney\r\nIce', 3.50, 'img/menu-img/strawberry smoothie.jpg', 7),
(22, 'GREEN SMOOTHIE', 'Healthy drink\r\nFresh juice\r\nGreen smoothie\r\nNatural ingredients\r\nVitamin rich\r\nBreakfast drink\r\nDetox drink\r\nLow calorie', 'Spinach\r\nGreen apple\r\nBanana\r\nKiwi\r\nHoney\r\nYogurt\r\nIce', 3.50, 'img/menu-img/green smoothie.jpg', 7),
(23, 'WATERMELON SMOOTHIE', 'Watermelon flavor\r\nRefreshing drink\r\nCold smoothie\r\nSummer beverage\r\nFresh fruit drink\r\nLight drink\r\nIced beverage\r\nHydrating drink', 'Watermelon\r\nMint\r\nLemon juice\r\nSugar\r\nIce', 3.50, 'img/menu-img/watermelon smoothie.jpg', 7),
(24, 'SODA', 'Soft drink\r\nFizzy drink\r\nCarbonated beverage\r\nCold soda\r\nSweet beverage\r\nSparkling drink\r\nCola flavor\r\nRefreshing soda', 'Carbonated water\r\nSugar\r\nFlavoring\r\nCaffeine\r\nIce', 3.00, 'img/menu-img/soda.jpg', 7),
(25, 'SWISS ROLL', 'Sponge cake\r\nRolled cake\r\nSoft dessert\r\nCream filling\r\nSweet pastry\r\nBakery dessert\r\nVanilla flavor\r\nLight cake', 'Flour\r\nEggs\r\nSugar\r\nButter\r\nMilk\r\nVanilla extract\r\nBaking powder\r\nWhipping cream\r\nStrawberry jam\r\nPowdered sugar\r\nSalt', 4.50, 'img/menu-img/swiss roll.avif', 6),
(26, 'TIRAMISU', 'Italian dessert\r\nCoffee flavor\r\nCreamy dessert\r\nCocoa topping\r\nLayered dessert\r\nSweet treat\r\nRich flavor\r\nClassic tiramisu', 'Mascarpone cheese\r\nLadyfingers\r\nEspresso coffee\r\nCocoa powder\r\nSugar\r\nEggs\r\nHeavy cream\r\nVanilla extract\r\nChocolate shavings\r\nMilk\r\nCoffee liqueur', 4.45, 'img/menu-img/tiramisu.jpg', 6),
(27, 'CHOCOLATE CAKE', 'Chocolate dessert\r\nRich cake\r\nSoft texture\r\nSweet cake\r\nBakery item\r\nChocolate flavor\r\nParty dessert\r\nHomemade cake', 'Flour\r\nCocoa powder\r\nSugar\r\nEggs\r\nButter\r\nMilk\r\nBaking powder\r\nVanilla extract\r\nDark chocolate\r\nChocolate chips\r\nHeavy cream\r\nSalt', 5.00, 'img/menu-img/chocolate cake.avif', 6),
(28, 'CHOCOLATE ICE CREAM', 'Frozen dessert\r\nChocolate flavor\r\nCold treat\r\nCreamy ice cream\r\nSweet dessert\r\nSummer treat\r\nChilled dessert\r\nIce cream scoop', 'Milk\r\nHeavy cream\r\nSugar\r\nCocoa powder\r\nDark chocolate\r\nVanilla extract\r\nEgg yolks\r\nChocolate syrup\r\nCondensed milk\r\nSalt', 4.75, 'img/menu-img/chocolate ice cream.jpg', 6);

-- --------------------------------------------------------

--
-- Table structure for table `orderitems`
--

CREATE TABLE `orderitems` (
  `IdOrderItems` int(11) NOT NULL,
  `Quantity` smallint(6) NOT NULL,
  `PriceAtTime` decimal(7,2) NOT NULL,
  `IdMenu` int(11) NOT NULL,
  `IdOrder` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orderitems`
--

INSERT INTO `orderitems` (`IdOrderItems`, `Quantity`, `PriceAtTime`, `IdMenu`, `IdOrder`) VALUES
(197, 1, 5.15, 15, 1);

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `IdOrder` int(11) NOT NULL,
  `OrderTotalAmount` decimal(7,2) NOT NULL,
  `SpecialInstructions` varchar(255) DEFAULT NULL,
  `Status` varchar(50) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `IdUser` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`IdOrder`, `OrderTotalAmount`, `SpecialInstructions`, `Status`, `created_at`, `IdUser`) VALUES
(1, 5.15, NULL, 'Confirmed', '2026-06-06 10:43:27', 6);

-- --------------------------------------------------------

--
-- Table structure for table `reservations`
--

CREATE TABLE `reservations` (
  `IdReservation` int(11) NOT NULL,
  `GuestNumber` smallint(6) NOT NULL,
  `SpecialNotes` varchar(255) DEFAULT NULL,
  `ReservationDate` date NOT NULL,
  `ReservationTime` time NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `IdTable` int(11) NOT NULL,
  `IdUser` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reservations`
--

INSERT INTO `reservations` (`IdReservation`, `GuestNumber`, `SpecialNotes`, `ReservationDate`, `ReservationTime`, `created_at`, `IdTable`, `IdUser`) VALUES
(6, 2, '', '2026-06-09', '17:30:00', '2026-06-06 10:44:09', 2, 6);

-- --------------------------------------------------------

--
-- Table structure for table `restauranttable`
--

CREATE TABLE `restauranttable` (
  `IdTable` int(11) NOT NULL,
  `TableNumber` int(11) NOT NULL,
  `TableCapacity` smallint(6) NOT NULL,
  `IsActive` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `restauranttable`
--

INSERT INTO `restauranttable` (`IdTable`, `TableNumber`, `TableCapacity`, `IsActive`) VALUES
(1, 1, 2, 1),
(2, 2, 2, 1),
(3, 3, 4, 1),
(4, 4, 4, 1),
(5, 5, 6, 1),
(6, 6, 8, 1),
(7, 7, 10, 1);

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `IdReviews` int(11) NOT NULL,
  `UserRating` smallint(6) NOT NULL CHECK (`UserRating` between 1 and 5),
  `RatingDescription` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `IdUser` int(11) NOT NULL,
  `IdMenu` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reviews`
--

INSERT INTO `reviews` (`IdReviews`, `UserRating`, `RatingDescription`, `created_at`, `IdUser`, `IdMenu`) VALUES
(3, 4, 'This is a Review', '2026-06-06 10:44:40', 6, 14);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `IdUser` int(11) NOT NULL,
  `UserName` varchar(50) NOT NULL,
  `UserEmail` varchar(255) NOT NULL,
  `IdGoogle` varchar(255) DEFAULT NULL,
  `UserPassword` varchar(255) DEFAULT NULL,
  `UserToken` varchar(255) DEFAULT NULL,
  `UserAvatar` varchar(255) DEFAULT NULL,
  `UserRole` varchar(50) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `reset_token` varchar(64) DEFAULT NULL,
  `reset_expires` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`IdUser`, `UserName`, `UserEmail`, `IdGoogle`, `UserPassword`, `UserToken`, `UserAvatar`, `UserRole`, `created_at`, `reset_token`, `reset_expires`) VALUES
(6, 'Test User', 'user@user.com', NULL, '$2y$10$Ltd8k2hgOVCQ9CpClT4XqOmZWRVlSyJpXLeko4ZS5lzOgDLu8tXva', NULL, NULL, 'user', '2026-06-06 10:43:05', NULL, NULL),
(7, 'Test admin', 'admin@smartbite.com', NULL, '$2y$10$zeJapfIM3/7mMLIxoGJybem/4GL.6GVU5wwx1p3qkcOdAMyf7M8u6', NULL, NULL, 'admin', '2026-06-06 10:46:16', NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `category`
--
ALTER TABLE `category`
  ADD PRIMARY KEY (`IdCategory`);

--
-- Indexes for table `menu`
--
ALTER TABLE `menu`
  ADD PRIMARY KEY (`IdMenu`),
  ADD UNIQUE KEY `ImageURL` (`ImageURL`),
  ADD KEY `IdCategory` (`IdCategory`);

--
-- Indexes for table `orderitems`
--
ALTER TABLE `orderitems`
  ADD PRIMARY KEY (`IdOrderItems`),
  ADD KEY `IdMenu` (`IdMenu`),
  ADD KEY `IdOrder` (`IdOrder`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`IdOrder`),
  ADD KEY `IdUser` (`IdUser`);

--
-- Indexes for table `reservations`
--
ALTER TABLE `reservations`
  ADD PRIMARY KEY (`IdReservation`),
  ADD KEY `IdTable` (`IdTable`),
  ADD KEY `IdUser` (`IdUser`);

--
-- Indexes for table `restauranttable`
--
ALTER TABLE `restauranttable`
  ADD PRIMARY KEY (`IdTable`),
  ADD UNIQUE KEY `TableNumber` (`TableNumber`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`IdReviews`),
  ADD KEY `IdUser` (`IdUser`),
  ADD KEY `IdMenu` (`IdMenu`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`IdUser`),
  ADD UNIQUE KEY `UserEmail` (`UserEmail`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `category`
--
ALTER TABLE `category`
  MODIFY `IdCategory` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `menu`
--
ALTER TABLE `menu`
  MODIFY `IdMenu` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `orderitems`
--
ALTER TABLE `orderitems`
  MODIFY `IdOrderItems` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=198;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `IdOrder` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=67;

--
-- AUTO_INCREMENT for table `reservations`
--
ALTER TABLE `reservations`
  MODIFY `IdReservation` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `restauranttable`
--
ALTER TABLE `restauranttable`
  MODIFY `IdTable` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `IdReviews` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `IdUser` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `menu`
--
ALTER TABLE `menu`
  ADD CONSTRAINT `menu_ibfk_1` FOREIGN KEY (`IdCategory`) REFERENCES `category` (`IdCategory`);

--
-- Constraints for table `orderitems`
--
ALTER TABLE `orderitems`
  ADD CONSTRAINT `orderitems_ibfk_1` FOREIGN KEY (`IdMenu`) REFERENCES `menu` (`IdMenu`),
  ADD CONSTRAINT `orderitems_ibfk_2` FOREIGN KEY (`IdOrder`) REFERENCES `orders` (`IdOrder`);

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`IdUser`) REFERENCES `users` (`IdUser`);

--
-- Constraints for table `reservations`
--
ALTER TABLE `reservations`
  ADD CONSTRAINT `reservations_ibfk_1` FOREIGN KEY (`IdTable`) REFERENCES `restauranttable` (`IdTable`),
  ADD CONSTRAINT `reservations_ibfk_2` FOREIGN KEY (`IdUser`) REFERENCES `users` (`IdUser`);

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`IdUser`) REFERENCES `users` (`IdUser`),
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`IdMenu`) REFERENCES `menu` (`IdMenu`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
