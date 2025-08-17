-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 17, 2025 at 02:25 PM
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
-- Database: `grocery_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

CREATE TABLE `cart` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cart`
--

INSERT INTO `cart` (`id`, `user_id`, `created_at`) VALUES
(3, 3, '2025-08-01 00:01:50'),
(4, 4, '2025-08-13 23:55:19');

-- --------------------------------------------------------

--
-- Table structure for table `cart_items`
--

CREATE TABLE `cart_items` (
  `id` int(11) NOT NULL,
  `cart_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `added_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cart_items`
--

INSERT INTO `cart_items` (`id`, `cart_id`, `product_id`, `quantity`, `added_at`) VALUES
(68, 3, 9, 1, '2025-08-17 00:24:20'),
(69, 3, 18, 1, '2025-08-17 00:24:21');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `description`) VALUES
(1, 'Fruits', 'Fresh fruits and berries'),
(2, 'Vegetables', 'Fresh vegetables and greens'),
(3, 'Dairy', 'Milk, cheese, and dairy products'),
(4, 'Bakery', 'Bread, pastries, and baked goods'),
(5, 'Beverages', 'Drinks and beverages'),
(6, 'Snacks', 'Chips, nuts, and snacks'),
(7, 'Meat', 'Fresh meat and poultry'),
(8, 'Seafood', 'Fish and seafood products'),
(9, 'Pantry', 'Canned goods and staples'),
(10, 'Frozen', 'Frozen foods and ice cream'),
(11, 'Tea', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `customer`
--

CREATE TABLE `customer` (
  `id` int(11) NOT NULL,
  `Fname` varchar(100) NOT NULL,
  `Lname` varchar(100) NOT NULL,
  `Mobile` varchar(20) DEFAULT NULL,
  `Building` varchar(255) DEFAULT NULL,
  `Block` varchar(100) DEFAULT NULL,
  `UID` int(11) NOT NULL,
  `Profile_pic` varchar(255) DEFAULT 'default.jpg',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customer`
--

INSERT INTO `customer` (`id`, `Fname`, `Lname`, `Mobile`, `Building`, `Block`, `UID`, `Profile_pic`, `created_at`) VALUES
(2, 'Steven', 'Jobs', '+973-3355-4411', 'Building e', 'Block 1234', 3, 'profile_3_1753995775.png', '2025-08-01 00:01:41'),
(3, 'Ali', 'Hassan', '+973-3945-5493', 'Building F', 'Block 4321', 4, 'profile_4_1755118812.png', '2025-08-13 23:55:04');

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_read` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `messages`
--

INSERT INTO `messages` (`id`, `name`, `email`, `subject`, `message`, `created_at`, `is_read`) VALUES
(1, 'Steven Jobs', 'steve@gmail.com', 'Feedback', 'Great website, sure to recommend my friends.', '2025-08-06 15:17:07', 1),
(6, 'quddus Mia', 'steve@gmail.com', 'General Inquiry', 'Will the kenyan meat be available by next week?', '2025-08-12 18:24:43', 1);

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `tax_amount` decimal(10,2) NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `status` varchar(50) DEFAULT 'pending',
  `shipping_address` text DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `subtotal`, `tax_amount`, `total`, `status`, `shipping_address`, `payment_method`, `created_at`) VALUES
(1, 3, 0.50, 0.05, 0.55, 'delivered', 'Building: Building e, Block: Block 1234', 'cash_on_delivery', '2025-08-01 00:03:09'),
(2, 3, 3.45, 0.35, 3.80, 'cancelled', 'Building: Building e, Block: Block 1234', 'cash_on_delivery', '2025-08-02 13:21:24'),
(3, 3, 8.86, 0.89, 9.74, 'delivered', 'Building: Building e, Block: Block 1234', 'credit_card', '2025-08-03 14:43:37'),
(4, 3, 44.31, 4.43, 48.74, 'cancelled', 'Building: Building e, Block: Block 1234', 'cash_on_delivery', '2025-08-05 16:47:16'),
(5, 3, 1.29, 0.13, 1.42, 'cancelled', 'Building: Building e, Block: Block 1234', 'cash_on_delivery', '2025-08-05 16:47:52'),
(6, 3, 2.99, 0.30, 3.28, 'cancelled', 'Building: Building e, Block: Block 1234', 'cash_on_delivery', '2025-08-05 16:49:28'),
(7, 3, 4.09, 0.41, 4.49, 'cancelled', 'Building: Building e, Block: Block 1234', 'cash_on_delivery', '2025-08-05 17:23:03'),
(8, 3, 8.17, 0.82, 8.99, 'cancelled', 'Building: Building e, Block: Block 1234', 'cash_on_delivery', '2025-08-05 17:32:33'),
(9, 3, 1.29, 0.13, 1.42, 'cancelled', 'Building: Building e, Block: Block 1234', 'cash_on_delivery', '2025-08-05 18:41:23'),
(10, 3, 10.75, 1.08, 11.83, 'cancelled', 'Building: Building e, Block: Block 1234', 'cash_on_delivery', '2025-08-05 23:01:11'),
(11, 3, 6.19, 0.62, 6.80, 'cancelled', 'Building: Building e, Block: Block 1234', 'credit_card', '2025-08-10 13:03:33'),
(12, 3, 0.19, 0.02, 0.21, 'cancelled', 'Building: Building e, Block: Block 1234', 'cash_on_delivery', '2025-08-10 15:22:56'),
(13, 3, 8.22, 0.82, 9.04, 'pending', 'Building: Building e, Block: Block 1234', 'cash_on_delivery', '2025-08-13 21:58:28'),
(14, 4, 15.99, 1.60, 17.58, 'pending', 'Building: Building f, Block: Block 4321', 'debit_card', '2025-08-13 23:56:36'),
(15, 3, 4.23, 0.42, 4.65, 'pending', 'Building: Building e, Block: Block 1234', 'cash_on_delivery', '2025-08-17 00:23:58');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `quantity`, `price`) VALUES
(3, 2, 2, 2, 0.60),
(4, 2, 3, 1, 0.60),
(5, 3, 9, 4, 1.10),
(6, 3, 16, 2, 1.70),
(7, 3, 10, 1, 1.07),
(8, 4, 9, 6, 1.10),
(9, 4, 18, 4, 1.50),
(10, 4, 28, 9, 0.19),
(11, 4, 24, 11, 0.53),
(12, 4, 10, 9, 1.07),
(13, 4, 16, 8, 1.70),
(14, 4, 17, 2, 0.55),
(15, 5, 9, 1, 1.10),
(16, 5, 28, 1, 0.19),
(17, 6, 9, 1, 1.10),
(18, 6, 28, 1, 0.19),
(19, 6, 16, 1, 1.70),
(20, 7, 9, 2, 1.10),
(21, 7, 28, 1, 0.19),
(22, 7, 16, 1, 1.70),
(23, 8, 9, 4, 1.10),
(24, 8, 28, 2, 0.19),
(25, 8, 16, 2, 1.70),
(26, 9, 9, 1, 1.10),
(27, 9, 28, 1, 0.19),
(28, 10, 9, 6, 1.10),
(29, 10, 28, 4, 0.19),
(30, 10, 16, 2, 1.70),
(31, 11, 9, 1, 1.10),
(32, 11, 16, 3, 1.70),
(33, 12, 28, 1, 0.19),
(34, 13, 18, 2, 1.50),
(35, 13, 17, 3, 0.55),
(36, 13, 26, 4, 0.90),
(37, 14, 34, 4, 0.85),
(38, 14, 22, 2, 3.13),
(39, 14, 27, 1, 0.29),
(40, 14, 3, 1, 0.60),
(41, 14, 12, 1, 0.75),
(42, 14, 10, 3, 1.07),
(43, 14, 29, 5, 0.30),
(44, 15, 18, 1, 1.50),
(45, 15, 9, 1, 1.10),
(46, 15, 10, 1, 1.07),
(47, 15, 28, 3, 0.19);

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `product_summary` text DEFAULT NULL,
  `price` decimal(10,3) NOT NULL,
  `original_price` decimal(10,3) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `features` text DEFAULT NULL,
  `specifications` text DEFAULT NULL,
  `inStock` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `description`, `product_summary`, `price`, `original_price`, `image`, `category_id`, `features`, `specifications`, `inStock`, `created_at`) VALUES
(1, 'Apple', '', NULL, 0.500, NULL, '1753995078_apple.jpg', 1, NULL, NULL, 1, '2025-07-31 23:51:18'),
(2, 'Lusine Milk Brown Bread 1pkt', 'The Lusine Sliced Brown Bread is a wholesome choice for a healthy start to your day. Crafted from superior quality, natural ingredients, it’s packed with essential nutrients to fuel your morning with energy. This whole grain bread is light, easy to digest, and delicious—perfect for breakfast, supper, or your child’s lunch box.\r\n\r\nThe pre-sliced loaf makes preparation effortless—toast it, butter it, or use it to create a variety of tasty recipes. Store it in a cool, dry place or in the refrigerator to keep it fresh for longer.', NULL, 0.600, NULL, '1754129738_Lusine Milk Brown Bread.jpg', 4, NULL, NULL, 1, '2025-08-02 13:15:38'),
(3, 'Lusine Sliced Brown Bread', 'Lusine Sliced Brown Bread is the perfect choice for a wholesome and nutritious start to your day. Made from premium-quality whole grain flour and natural ingredients, it delivers rich flavor along with essential nutrients your body needs. The bread’s soft, airy texture and mild taste make it suitable for all ages, while its high fiber content supports healthy digestion and sustained energy throughout the day.\r\n\r\nEach loaf comes conveniently pre-sliced, making it easy to prepare quick and delicious meals. Simply toast it for breakfast, use it as the base for sandwiches, or enjoy it with spreads, eggs, or soups. It’s also a great choice for lunch boxes, providing a healthy alternative to refined white bread.\r\n\r\nFor the best freshness, store Lusine Sliced Brown Bread in a cool, dry place or refrigerate it to extend its shelf life.', NULL, 0.600, NULL, '1754129811_Lusine Sliced Brown Bread.jpg', 4, NULL, NULL, 1, '2025-08-02 13:16:51'),
(4, 'Tomato', 'Fresh tomatoes are packed with essential vitamins and minerals that promote overall health. Juicy and flavourful, they are one of the richest natural sources of lycopene — a powerful antioxidant linked to numerous health benefits. They’re also loaded with vitamin C, potassium, folate, and vitamin K. Low in carbs and made up of about 95% water, fresh tomatoes are an excellent hydrating choice for any diet plan.\r\n\r\nWith their slightly tart and refreshing flavour, tomatoes are perfect for enhancing a wide variety of dishes. Enjoy them raw in wraps, sandwiches, and salads, or use them to make rich sauces and fresh salsas. Their versatility makes them a must-have ingredient in every kitchen.\r\n\r\nWe ensure that our tomatoes meet the highest standards of quality and freshness, so you can enjoy them at their very best.', NULL, 0.285, NULL, '1754129993_tomato.jpg', 1, NULL, NULL, 1, '2025-08-02 13:19:53'),
(7, 'Onion 1kg', 'Fresh Saudi onions are a kitchen essential, adding depth, aroma, and flavor to a wide range of dishes. Known for their crisp texture and strong, savory taste, these onions can be enjoyed raw, sautéed, roasted, or caramelized. They are a rich source of vitamins, minerals, and antioxidants, making them a healthy addition to your daily meals.\r\n\r\nOnions are naturally low in calories but high in nutrients like vitamin C, vitamin B6, folate, and potassium. They also contain beneficial plant compounds such as quercetin, which supports heart health and boosts immunity. Whether you’re preparing salads, curries, soups, or grilled dishes, Saudi onions bring both taste and nutrition to your table.', NULL, 0.225, NULL, '1754130302_1753693043_onion.jpg', 2, NULL, NULL, 1, '2025-08-02 13:25:02'),
(9, 'Almarai Full Fat Fresh Milk 2 Litres', 'Relish your early-morning breakfast cereal with a healthier alternative of Almarai Fresh Milk Full Fat. This 100% pure cow\'s milk offers a full fat and delicious version of dairy foods. Plus, the milk has zero water, milk powder, and additives, ensuring safe consumption. By including this milk in the regular diet, you pave a clear path towards healthy living. Enclosed in a practical bottle with an easy-grip handle, the fresh cow\'s milk enables effortless dispensing and consumption.', NULL, 1.100, NULL, '1754132806_milk.jpg', 3, NULL, NULL, 1, '2025-08-02 14:06:46'),
(10, 'Banana Chiquita Ecuador 1 kg', 'Give your body a natural nutritional boost with fresh Chiquita bananas grown in the fertile lands of Ecuador. Naturally sweet, soft, and creamy, these bananas are a delicious and healthy snack for any time of day. Each banana is carefully selected to ensure the highest quality, freshness, and flavor. Bananas are rich in essential nutrients, including magnesium and potassium, which support heart and muscle health. They’re also a powerhouse of beta carotene, a good source of fiber, and provide natural energy to keep you going throughout the day.', NULL, 1.065, NULL, '1754133152_banana2.jpg', 1, NULL, NULL, 1, '2025-08-02 14:10:12'),
(11, 'Fresh Carrots 1 kg', 'Serve a delicious side dish of roasted and garnished fresh Australian carrots alongside your main course for a gourmet-style meal. Grown in ideal conditions and carefully harvested, these carrots are known for their superior quality, crisp texture, and naturally sweet flavor. They are a staple in kitchens worldwide, valued for both their taste and versatility.\r\n\r\nWhen included as a regular part of your diet, carrots provide a wealth of health benefits. They are high in vitamins, minerals, and antioxidants, including dietary fiber, vitamin K, and beta carotene, which supports healthy vision, skin, and overall well-being.\r\n\r\nBenefits:\r\n● Excellent source of vitamin K, fiber, and beta carotene\r\n● Rich in antioxidants that support overall health\r\n● Naturally sweet flavor that appeals to all ages\r\n● Low in calories and high in nutrition\r\n\r\nStorage & Uses:\r\n● Keep refrigerated; do not freeze\r\n● Enjoy raw in salads, coleslaw, and vegetable sticks\r\n● Roast, steam, or sauté as a delicious side dish\r\n● Combine with other vegetables for hearty meals like stews, curries, or kichadi\r\n\r\nInteresting Fact:\r\nCarrots are one of the richest natural sources of beta carotene, the compound that gives them their vibrant orange color.', NULL, 0.590, NULL, '1754133437_carrot.jpg', 1, NULL, NULL, 1, '2025-08-02 14:17:17'),
(12, 'Lusine Sliced Bread Brioche 320g', 'Lusine Sliced Brioche Bread brings a touch of indulgence to your everyday meals. Soft, fluffy, and mildly sweet, this bread is crafted from high‑quality ingredients to deliver a rich, buttery flavour in every bite. Perfectly pre‑sliced for convenience, it’s ideal for making sandwiches, toasts, or enjoying on its own with butter or jam.\r\n\r\nBrioche bread is not only delicious but also versatile, making it suitable for breakfast, snacks, or even as a base for gourmet recipes. Its soft texture and golden crust make it a family favourite that pairs beautifully with both sweet and savoury toppings.', NULL, 0.750, NULL, '1754133569_bread.jpg', 4, NULL, NULL, 1, '2025-08-02 14:19:29'),
(13, 'Karami Rice 5kg', 'Karami Rice is a premium‑quality grain, perfect for preparing fluffy, aromatic, and flavorful meals. Carefully sourced and processed to maintain its natural taste and texture, this rice is ideal for a variety of dishes, from traditional biryani and pilaf to simple steamed rice for everyday meals.\r\n\r\nIts long grains cook to perfection without sticking, making it a favorite choice for both home cooking and special occasions. Karami Rice is naturally gluten‑free and a good source of carbohydrates, providing the energy you need for an active lifestyle.\r\n\r\nBenefits:\r\n● Premium‑quality rice with long, fluffy grains\r\n● Naturally gluten‑free and rich in carbohydrates\r\n● Suitable for a wide range of savory dishes\r\n● Ideal for both daily cooking and festive meals\r\n\r\nStorage & Uses:\r\n● Store in a cool, dry place away from direct sunlight\r\n● Rinse before cooking for the best texture\r\n● Perfect for biryani, pilaf, fried rice, or plain steamed rice\r\n● Pairs well with curries, grilled meats, and vegetable dishes\r\n\r\nInteresting Fact:\r\nRice is a staple food for over half the world’s population and is one of the most widely consumed grains globally.', NULL, 2.790, NULL, '1754134197_karami rice 5kg.jpg', 9, NULL, NULL, 1, '2025-08-02 14:29:57'),
(14, 'Lazeez Basmati rice 5kg', 'Lazeez Basmati Rice is known for its long, slender grains and rich aroma, making it the perfect choice for creating flavourful and elegant meals. Carefully aged and processed, this premium basmati rice cooks to perfection—light, fluffy, and non‑sticky—enhancing the taste of both traditional and modern recipes.\r\n\r\nIdeal for biryani, pulao, fried rice, or simply served as steamed rice, Lazeez Basmati adds a touch of luxury to everyday dining. Naturally gluten‑free and rich in carbohydrates, it provides lasting energy while complementing a variety of dishes.\r\n\r\nBenefits:\r\n● Long‑grain basmati with delicate aroma and taste\r\n● Naturally gluten‑free and rich in carbohydrates\r\n● Cooks to perfection without sticking\r\n● Perfect for both daily cooking and special occasions\r\n\r\nStorage & Uses:\r\n● Store in a cool, dry place away from direct sunlight\r\n● Rinse before cooking for best texture and aroma\r\n● Excellent for biryani, pulao, fried rice, or plain steamed rice\r\n● Pairs beautifully with curries, grilled meats, and vegetable dishes\r\n\r\nInteresting Fact:\r\nBasmati rice is traditionally grown in the Himalayan foothills and is prized worldwide for its unique aroma and delicate flavour.', NULL, 2.490, NULL, '1754134659_Lazeez 5kg rice.jpg', 9, NULL, NULL, 1, '2025-08-02 14:35:21'),
(15, 'Mutton Shoulder Pakistan 1kg', 'Fresh Mutton Shoulder from Pakistan offers tender, flavorful meat perfect for a variety of hearty dishes. Known for its rich taste and succulent texture, this cut is ideal for slow-cooking methods such as roasting, stewing, or braising, bringing out deep, savory flavors that enhance traditional recipes.\r\n\r\nSourced from quality farms and handled with care, this mutton shoulder ensures freshness and premium quality with every purchase. Packed with protein, essential vitamins, and minerals, it’s a nutritious choice for meat lovers seeking wholesome meals.\r\n\r\nBenefits:\r\n● Tender and flavorful cut ideal for slow cooking\r\n● Rich source of protein, iron, and essential nutrients\r\n● Suitable for roasting, stewing, and curries\r\n● Sourced from trusted farms ensuring freshness and quality\r\n\r\nStorage & Uses:\r\n● Keep refrigerated and consume within 2-3 days for best quality\r\n● Can be frozen for extended storage; thaw before cooking\r\n● Perfect for traditional dishes like mutton curry, stew, and roast\r\n● Enhances flavor in slow-cooked meals and grills', NULL, 4.995, NULL, '1754135120_pakistani mutton.jpg', 7, NULL, NULL, 1, '2025-08-02 14:45:20'),
(16, 'Alyoum Fresh Chicken 1.1kg', 'Host a lavish dinner event by preparing a delicious gourmet roast of the Al Youm Fresh Chicken. Its tender meat cooks perfectly to release a wafting aroma that invokes an irresistible temptation to take the first bite. Once prepared, the meat’s succulent texture and mouth-watering flavour not just delight your taste buds but also captivate your inner foodie. With a high percentage of protein, energy, and vitamins, the chicken serves as a reliable food source to sustain a healthy living. In addition, it is derived from clean and disease-free fowls fed on a 100% vegetable diet. The chicken is a versatile food form, which can be combined with various culinary ingredients to present diverse, world-class cuisines. For convenience, the meat is processed as per the halal norms to meet your requirements.', NULL, 1.695, NULL, '1754138550_alyoum chicken1.jpg', 7, NULL, NULL, 1, '2025-08-02 15:42:30'),
(17, 'Apple red USA 1kg', 'Savour the delectable taste of Fresh Red Apples sourced from the farms of USA. These apples are not only tasty on their own or when added to meals, but they also have several health advantages. We strive to ensure that the products are of a high standard of quality and meet the requirements of food safety. Our team constantly and carefully monitor what we have in stock to recognize the freshest items and to assure it\'s of the best possible quality. BENEFITS: Apples are high in fibre, vitamins, and minerals. They can help provide one-fourth of the daily necessary Vitamin C requirements. STORAGE AND USES: Keep refrigerated, do not freeze. You can use them to prepare delicious smoothies, fruit salads, raw food preparations. They are the best choice for a tasty dietary snack that leads to a healthy lifestyle. They can also be used to make jams, juices, cakes, puddings, and more. INTERESTING FACT. The Chinese term for apples is pronounced \'ping,\' which also stands for peace. This is why, while visiting someone in China, apples are a preferred gift.', NULL, 0.550, NULL, '1754219407_apple.jpg', 1, NULL, NULL, 1, '2025-08-03 14:10:07'),
(18, 'Al Saudi Mutton Mince 4X400Gm', 'Al Saudi Mutton Mince is made from premium-quality mutton, finely ground for easy cooking and versatile use in a wide range of recipes. With its rich flavor and tender texture, this mince is perfect for preparing juicy kebabs, flavorful curries, delicious meatballs, or savory pies.\r\n\r\nPacked with protein, vitamins, and minerals, mutton mince is a nutritious choice that adds both taste and nourishment to your meals. Conveniently packed in four separate 400g portions, it allows for easy storage and use according to your needs.\r\n\r\nBenefits:\r\n● Premium-quality ground mutton with rich flavor\r\n● High in protein, iron, and essential nutrients\r\n● Conveniently portioned for easy cooking and storage\r\n● Ideal for kebabs, curries, meatballs, and pies\r\n\r\nStorage & Uses:\r\n● Keep refrigerated and use within a few days\r\n● Can be frozen for longer storage; thaw before cooking\r\n● Perfect for grilling, frying, or slow-cooking recipes\r\n● Adds depth and richness to a variety of dishes\r\n\r\nInteresting Fact:\r\nMutton mince absorbs spices and seasonings exceptionally well, making it a favorite in many Middle Eastern, South Asian, and Mediterranean cuisines.', NULL, 1.495, NULL, '1754219870_Al Saudi Mutton Mince 4X400Gm.jpg', 7, NULL, NULL, 1, '2025-08-03 14:17:50'),
(19, 'Mutton Back Leg Bangladesh 1kg', 'Fresh Mutton Back Leg from Bangladesh is a premium cut known for its lean, tender meat and rich flavor. Perfect for roasting, grilling, or slow-cooking, this cut delivers a delicious balance of taste and texture, making it ideal for both everyday meals and special occasions.\r\n\r\nSourced from trusted farms and handled with the utmost care, this mutton leg offers high-quality protein, iron, and essential vitamins to support a healthy diet. Its naturally lean profile makes it a healthier choice for meat lovers who enjoy full-bodied flavor without excess fat.\r\n\r\nBenefits:\r\n● Lean and tender premium cut from the back leg\r\n● Excellent source of protein, iron, and essential nutrients\r\n● Ideal for roasting, grilling, or slow-cooked recipes\r\n● Sourced from trusted farms for guaranteed freshness\r\n\r\nStorage & Uses:\r\n● Keep refrigerated and consume within 2–3 days\r\n● Can be frozen for extended storage; thaw fully before cooking\r\n● Perfect for mutton roast, curries, biryani, and grilled dishes\r\n● Works well in both traditional and modern recipes\r\n\r\nInteresting Fact:\r\nThe back leg cut is prized for its tenderness and versatility, making it one of the most popular choices for festive and family meals around the world.', NULL, 4.590, NULL, '1754220530_mutton leg.jpg', 7, NULL, NULL, 1, '2025-08-03 14:28:50'),
(20, 'Newzealand Beef Silver Side 1kg', 'New Zealand Beef Silverside is a premium lean cut from the hindquarter, celebrated for its firm texture, delicate marbling, and deep, savory flavor. Raised on the lush, open pastures of New Zealand, these grass-fed cattle produce beef that is naturally tender and packed with nutrients. Silverside is a versatile choice in the kitchen — ideal for slow roasting to create a succulent family roast, simmering gently for traditional corned beef, or slicing thin for flavorful cold cuts. Its low-fat, high-protein profile makes it a healthy yet satisfying option for everyday meals, while its rich taste elevates special occasion dishes. Carefully selected and expertly prepared, this cut offers consistent quality, exceptional flavor, and the wholesome goodness of premium New Zealand beef in every bite.', NULL, 3.136, NULL, '1754220889_Newzealand Beef Silver Side 1kg.jpg', 7, NULL, NULL, 1, '2025-08-03 14:34:49'),
(21, 'Lusine Bran Sliced Bread 1pkt', 'Lusine Bran Sliced Bread is a wholesome, high-fiber bread made with the goodness of wheat bran for a healthy and satisfying start to your day. Soft, flavorful, and baked to perfection, it’s rich in dietary fiber to support digestion and overall well-being. The pre-sliced loaf makes it easy to prepare quick and nutritious meals — from simple toasts to hearty sandwiches. Naturally tasty and versatile, this bread is a great choice for breakfast, lunch, or light snacks.', NULL, 0.650, NULL, '1754221896_Lusine Bran Sliced Bread 1pkt.jpg', 4, NULL, NULL, 1, '2025-08-03 14:49:32'),
(22, 'Peperoni Pizza (Medium)', 'Pepperoni Pizza is the ultimate comfort food, featuring a perfectly baked golden crust topped with a rich, tangy tomato sauce, melted mozzarella cheese, and generous slices of spicy, savory pepperoni. Each bite delivers a delicious harmony of crispy edges, gooey cheese, and bold, zesty flavors that satisfy any craving. Whether enjoyed as a quick weeknight dinner or shared at parties, this pizza offers a convenient and flavorful meal that’s sure to please everyone at the table.', NULL, 3.125, NULL, '1754222426_pepproni pizza.jpg', 4, NULL, NULL, 1, '2025-08-03 15:00:26'),
(23, 'Potato 1kg', 'Fresh potatoes are a versatile kitchen essential, valued for their mild flavor, satisfying texture, and endless culinary uses. Rich in carbohydrates, fiber, vitamin C, and potassium, they provide both nourishment and comfort in every serving. Whether boiled, mashed, roasted, fried, or baked, potatoes adapt to a variety of recipes, from hearty stews and curries to crispy fries and creamy soups. Their ability to absorb flavours makes them a perfect companion to countless dishes, while their long shelf life ensures they’re always ready for your next meal.', NULL, 0.270, NULL, '1754248615_potato.jpg', 2, NULL, NULL, 1, '2025-08-03 22:16:55'),
(24, 'Cucumber 1kg', 'Fresh Saudi cucumbers are crisp, juicy, and refreshing, making them a perfect choice for salads, snacks, and light meals. Naturally hydrating and low in calories, they are packed with water, fiber, and essential nutrients like vitamin K, vitamin C, and potassium. Their mild flavor and crunchy texture make them ideal for enjoying raw, adding to sandwiches, blending into smoothies, or pickling for a tangy treat. Whether as a healthy snack or a cooling side dish, Saudi cucumbers bring freshness and nutrition to your table every day.', NULL, 0.525, NULL, '1754249114_cucumber.jpg', 2, NULL, NULL, 1, '2025-08-03 22:25:14'),
(25, 'Lettuce 1kg', 'Fresh lettuce offers a crisp, refreshing texture and mild flavor that makes it a versatile ingredient in a variety of dishes. Low in calories and naturally hydrating, it’s an excellent choice for healthy meals and light snacks. Packed with vitamins A and K, folate, and fiber, lettuce supports overall wellness while adding freshness and crunch to your plate.\r\n\r\nBenefits:\r\n● Low in calories, high in water content for hydration\r\n● Rich in vitamins A and K for skin, eye, and bone health\r\n● Contains folate and fiber for digestive wellness\r\n● Adds freshness and crunch to meals\r\n\r\nStorage & Uses:\r\n● Keep refrigerated; store in a perforated bag for freshness\r\n● Use as a base for salads and healthy wraps\r\n● Add to sandwiches, burgers, and spring rolls for extra crunch\r\n● Perfect as a garnish for main dishes\r\n\r\nInteresting Fact:\r\nLettuce is one of the oldest cultivated vegetables, believed to have been grown in ancient Egypt over 4,000 years ago.', NULL, 0.795, NULL, '1754249417_Lettuce.jpg', 2, NULL, NULL, 1, '2025-08-03 22:30:17'),
(26, 'Ginger 1kg', 'Fresh ginger is a fragrant, spicy root prized for its distinctive flavor and numerous health benefits. Naturally rich in antioxidants and bioactive compounds like gingerol, it supports digestion, boosts immunity, and helps reduce inflammation. Its warm, zesty taste makes it a versatile ingredient in both savory and sweet dishes — from curries, stir-fries, and soups to teas, baked goods, and marinades. Whether used fresh, grated, sliced, or crushed, ginger adds depth, aroma, and a burst of freshness to your cooking while also offering natural wellness benefits.', NULL, 0.895, NULL, '1754250498_ginger.jpg', 2, NULL, NULL, 1, '2025-08-03 22:48:18'),
(27, 'Orange Valencia 1kg', 'Fresh Valencia oranges are sweet, juicy, and bursting with refreshing citrus flavor, making them a perfect choice for snacking, juicing, or adding to salads and desserts. Rich in vitamin C, antioxidants, and dietary fiber, they help boost immunity, support skin health, and aid digestion. Known for their bright color and smooth texture, Valencia oranges are ideal for making fresh, naturally sweet orange juice. Whether enjoyed on their own, blended into smoothies, or used as a zesty ingredient in your recipes, these oranges deliver freshness and nutrition in every bite.', NULL, 0.290, NULL, '1754250951_orange valencia.jpg', 1, NULL, NULL, 1, '2025-08-03 22:55:51'),
(28, 'Alsi Cola 200ml', 'Alsi Cola is a distinctive and refreshing soft drink proudly made in Saudi Arabia. Combining the classic, fizzy cola taste with a subtle infusion of alsi (flaxseed) essence, it offers a unique and mildly nutty flavor that sets it apart from traditional colas. This cool drink is perfect for quenching your thirst on hot days while delivering a delicious twist that reflects regional tastes and preferences. Whether enjoyed chilled on its own or alongside your favorite meals, Alsi Cola brings a refreshing burst of flavor with a touch of cultural uniqueness.', NULL, 0.190, NULL, '1754251846_Alsi cola.jpg', 5, NULL, NULL, 1, '2025-08-03 23:10:46'),
(29, 'Kinza Blackcurrant 360ml', 'Kinza Blackcurrant 360ml is a refreshing Saudi-made soft drink that blends the bold, tangy sweetness of ripe blackcurrants with a sparkling fizzy kick. Perfectly balanced to deliver a burst of fruity flavor in every sip, it’s an ideal choice for cooling down on hot days or enjoying alongside your favorite snacks and meals. Best served chilled, this vibrant beverage offers a uniquely delicious taste that stands out among fruit-flavored sodas.', NULL, 0.300, NULL, '1754252137_kinza blackcurrant.jpg', 5, NULL, NULL, 1, '2025-08-03 23:15:37'),
(30, 'Kinza Pomegranate 360ml', 'Kinza Pomegranate 360ml is a refreshing Saudi-made soft drink that combines the naturally tangy-sweet taste of pomegranate with a lively fizzy kick. Its bold flavor and crisp carbonation make it a perfect choice for quenching your thirst on hot days or pairing with snacks and meals. Best served chilled, this vibrant drink delivers a fruity burst of flavor that stands out among other carbonated beverages, offering both refreshment and a unique taste experience.', NULL, 0.300, NULL, '1754252382_kinza pomegranate.jpg', 5, NULL, NULL, 1, '2025-08-03 23:19:42'),
(31, 'Kinza Orange Carbonated Drink 360ml x6', 'Kinza Orange Carbonated Drink 360ml x6 is a refreshing Saudi-made beverage that blends the bright, zesty flavor of ripe oranges with lively carbonation for a crisp and satisfying taste. Bursting with citrusy sweetness and tang, it’s perfect for cooling down on hot days or enjoying alongside snacks and meals. Conveniently packed in a set of six, it’s ideal for sharing with family and friends or keeping stocked for everyday refreshment. Best served chilled, this orange soda delivers a fruity, fizzy burst in every sip.', NULL, 1.750, NULL, '1754252616_kinza orange pack.jpg', 5, NULL, NULL, 1, '2025-08-03 23:23:36'),
(32, 'Kinza Citrus Drink 360ml x24', 'Kinza Citrus Carbonated Drink is a refreshing Saudi-made beverage that combines the tangy, zesty flavors of mixed citrus fruits with lively carbonation for a crisp and invigorating taste. Perfectly balanced between sweet and tart, it’s an ideal thirst-quencher on hot days and a flavorful companion to snacks and meals. This bulk pack of 24 is perfect for parties, gatherings, or keeping well-stocked at home for everyday enjoyment. Best served chilled, Kinza Citrus delivers a sparkling burst of citrus freshness in every sip.', NULL, 6.400, NULL, '1754253398_kinza lime .jpg', 5, NULL, NULL, 0, '2025-08-03 23:36:38'),
(34, 'Mackerel Fish 1kg', 'Fresh mackerel fish is prized for its rich flavor, firm texture, and high nutritional value. Packed with omega‑3 fatty acids, high‑quality protein, and essential vitamins and minerals, it supports heart health, brain function, and overall wellness. Its naturally oily flesh makes it perfect for grilling, baking, smoking, or pan‑frying, delivering a deliciously moist and flavorful result. Whether prepared with simple seasonings or incorporated into curries, stews, or salads, mackerel offers a wholesome and satisfying seafood choice. Best enjoyed fresh and cooked the same day for optimal taste and quality.', NULL, 0.850, NULL, '1754475466_macherel fish.jpg', 8, NULL, NULL, 1, '2025-08-06 13:17:46'),
(35, 'Sherry Fish', 'Fresh Sherry fish is a popular choice in Middle Eastern cuisine, known for its mild flavor, tender white flesh, and versatility in cooking. Naturally rich in protein, vitamins, and essential minerals, it makes a healthy and delicious addition to your diet. Sherry fish can be grilled, fried, baked, or used in curries and stews, absorbing spices and seasonings beautifully while maintaining its delicate texture. Perfect for family meals or special occasions, this fish delivers both great taste and valuable nutrition in every bite. For the best flavor, cook and enjoy it fresh.', NULL, 1.850, NULL, '1754477110_sherry fish.jpg', 8, NULL, NULL, 1, '2025-08-06 13:45:10'),
(36, 'Salmon Fish Iran 1kg', 'Fresh Iranian salmon is prized for its rich, buttery flavor, tender texture, and exceptional nutritional value. Packed with omega‑3 fatty acids, high‑quality protein, and essential vitamins like B12 and D, it supports heart health, brain function, and overall well-being. Its vibrant pink flesh cooks beautifully, whether grilled, baked, pan‑seared, or steamed, delivering a moist and flavorful result every time. Perfect for gourmet dishes or healthy everyday meals, Iranian salmon pairs wonderfully with fresh herbs, citrus, and a variety of seasonings, making it a versatile and delicious choice for seafood lovers.', NULL, 1.950, NULL, '1754506292_Salmon fish iran.jpg', 8, NULL, NULL, 1, '2025-08-06 21:51:32'),
(37, 'Oman Salad Chips 22g', 'Oman Salad Chips are a deliciously crunchy snack made from premium-quality potatoes, offering a unique hot and sour flavor that delivers a lip-smacking treat with every bite. Their natural aroma and crisp texture make snack time more enjoyable, whether at home or on the go. Perfect to enjoy with both hot and cold beverages, these chips are an ideal companion for picnics, movies, or casual gatherings. Proudly made in the Sultanate of Oman, they bring authentic taste and irresistible crunch to every moment.', NULL, 0.110, NULL, '1755433341_salad chips.jpg', 6, NULL, NULL, 1, '2025-08-17 15:22:21');

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `id` int(11) NOT NULL,
  `Username` varchar(100) NOT NULL,
  `Email` varchar(150) NOT NULL,
  `Password` varchar(255) NOT NULL,
  `Type` enum('Customer','Admin') NOT NULL DEFAULT 'Customer',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`id`, `Username`, `Email`, `Password`, `Type`, `created_at`) VALUES
(1, 'admin', 'admin@freshnest.com', '$2y$10$IUOOVmWTpOsfqhVkOYurReVCggEs5vuvD7PN2Qx5XPl562sqE0hqq', 'Admin', '2025-07-31 23:50:07'),
(3, 'Steve01', 'steve@gmail.com', '$2y$10$hCEER3aELvEPrFlWJdNJGuEEtqcPG9V8dn9Bb.3cfM9/fJmzZrJSm', 'Customer', '2025-08-01 00:01:41'),
(4, 'AliHassan11', 'ali123@gmail.com', '$2y$10$6SjcJHSyPo9XMeG.fKb61.GV6i8Pm5FR.16xJEydhJLh.LSMy6RJC', 'Customer', '2025-08-13 23:55:04');

-- --------------------------------------------------------

--
-- Table structure for table `wishlist`
--

CREATE TABLE `wishlist` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `wishlist`
--

INSERT INTO `wishlist` (`id`, `user_id`, `product_id`, `created_at`) VALUES
(10, 3, 10, '2025-08-04 12:45:27'),
(17, 3, 17, '2025-08-10 00:31:57'),
(18, 3, 16, '2025-08-10 00:31:58'),
(19, 3, 28, '2025-08-10 00:31:59'),
(20, 3, 9, '2025-08-10 00:31:59'),
(21, 3, 32, '2025-08-13 14:16:46'),
(22, 3, 18, '2025-08-13 21:12:40');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_cart` (`user_id`);

--
-- Indexes for table `cart_items`
--
ALTER TABLE `cart_items`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_cart_product` (`cart_id`,`product_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `customer`
--
ALTER TABLE `customer`
  ADD PRIMARY KEY (`id`),
  ADD KEY `UID` (`UID`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `Username` (`Username`),
  ADD UNIQUE KEY `Email` (`Email`);

--
-- Indexes for table `wishlist`
--
ALTER TABLE `wishlist`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`,`product_id`),
  ADD KEY `product_id` (`product_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `cart_items`
--
ALTER TABLE `cart_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=70;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `customer`
--
ALTER TABLE `customer`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `wishlist`
--
ALTER TABLE `wishlist`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `cart_items`
--
ALTER TABLE `cart_items`
  ADD CONSTRAINT `cart_items_ibfk_1` FOREIGN KEY (`cart_id`) REFERENCES `cart` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cart_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `customer`
--
ALTER TABLE `customer`
  ADD CONSTRAINT `customer_ibfk_1` FOREIGN KEY (`UID`) REFERENCES `user` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `wishlist`
--
ALTER TABLE `wishlist`
  ADD CONSTRAINT `wishlist_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `wishlist_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
