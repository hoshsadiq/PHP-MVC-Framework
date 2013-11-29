-- MySQL dump 10.13  Distrib 5.5.25a, for Win32 (x86)
--
-- Host: localhost    Database: overheard
-- ------------------------------------------------------
-- Server version	5.5.25a

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Current Database: `overheard`
--

/*!40000 DROP DATABASE IF EXISTS `overheard`*/;

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `overheard` /*!40100 DEFAULT CHARACTER SET latin1 */;

USE `overheard`;

--
-- Table structure for table `posts`
--

DROP TABLE IF EXISTS `posts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `posts` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `time` int(11) unsigned NOT NULL,
  `ip` varbinary(16) NOT NULL COMMENT 'Binary representation of IPv4/IPv6. Use PHP''s inet_pton() and inet_ntop()',
  `content` text NOT NULL,
  `location` varchar(60) DEFAULT NULL COMMENT 'Location name as supplied by OP',
  `lat` float(10,6) DEFAULT NULL COMMENT 'Latitude',
  `long` float(10,6) DEFAULT NULL COMMENT 'Longtitude',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `posts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `posts`
--

LOCK TABLES `posts` WRITE;
/*!40000 ALTER TABLE `posts` DISABLE KEYS */;
INSERT INTO `posts` VALUES (2,2,1337367199,'\0','bitch please','Canterbury',NULL,NULL),(4,3,1337775184,'\0','Two males students on my road as two 10-year old school girls walk past..\n\nGuy 1: Check those two bitches out. How should I approach them?\nGuy 2: Just be yourself, they\'ll like you.','Canterbury',NULL,NULL),(5,0,1340230201,'\0\0','Test','',NULL,NULL),(6,0,1340230581,'\0\0','Test','',NULL,NULL),(7,0,1340231500,'\0\0','Hello?','',NULL,NULL),(8,0,1340231595,'\0\0','SOME RANDOM OVERHEARD SHIT :D','',NULL,NULL),(9,0,1344813794,'\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0','Test???','Canterbury',NULL,NULL),(10,0,1344885075,'\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0','test','',NULL,NULL),(11,0,1344886130,'\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0','wat','',NULL,NULL);
/*!40000 ALTER TABLE `posts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sessions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `password` varchar(32) NOT NULL,
  `sessid` varchar(32) NOT NULL,
  `userid` int(10) unsigned NOT NULL,
  `start` int(10) unsigned NOT NULL,
  `ip` varbinary(16) NOT NULL COMMENT 'Binary representation of IPv4/IPv6. Use PHP''s inet_pton() and inet_ntop()',
  PRIMARY KEY (`id`),
  UNIQUE KEY `sessid` (`sessid`),
  KEY `userid` (`userid`),
  CONSTRAINT `sessions_ibfk_1` FOREIGN KEY (`userid`) REFERENCES `users` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sessions`
--

LOCK TABLES `sessions` WRITE;
/*!40000 ALTER TABLE `sessions` DISABLE KEYS */;
INSERT INTO `sessions` VALUES (1,'334edc6accfc3bd13f95984cef10b108','Ujntydim3HLsrV1AFkq5JPuzejYDInt8',2,1339343438,'0'),(2,'334edc6accfc3bd13f95984cef10b108','Rgkq5JPuzejY4INtydSXChm27LrwbQVA',2,1339344819,'0'),(3,'334edc6accfc3bd13f95984cef10b108','fFaE9eINinSXsxC7BGLglQVqvz59eIei',2,1339344826,'0'),(4,'334edc6accfc3bd13f95984cef10b108','eTbHd7qwrlelQKDaEz4W4y3V3xsWPWrV',2,1339526409,'0'),(5,'334edc6accfc3bd13f95984cef10b108','TTnty38chmRW26bgkqvA5aPUpuyDIMSX',2,1339537218,'0'),(6,'334edc6accfc3bd13f95984cef10b108','DdHchmRW1wAFKQUquzEINintyCHMSW27',2,1339541786,'0'),(7,'334edc6accfc3bd13f95984cef10b108','MMRW2GLrwbgVZFJq49NTyDiX3Hms7cRw',2,1339541867,'0'),(8,'334edc6accfc3bd13f95984cef10b108','v5zEjp49eTXDin37MRwcgW1FKq6aPvzf',2,1339541880,'0'),(9,'334edc6accfc3bd13f95984cef10b108','cLRVAFKqvafUzEjp4INSyCHms7LswbgV',2,1339541918,'0'),(10,'334edc6accfc3bd13f95984cef10b108','WWrVrvA5zE9eINinSXsx27cGLglrV15A',2,1339542485,'0'),(11,'334edc6accfc3bd13f95984cef10b108','HXs7cRWBgl16KqvaPUzeTYDin3HMs8Ms',2,1339585835,'0'),(12,'334edc6accfc3bd13f95984cef10b108','HGLglrw16afkpuzEINTY38dhmsxBGLRw',2,1339688070,'0'),(13,'334edc6accfc3bd13f95984cef10b108','gfKPUZ5zEINTX38dhmswBGlQwAFKPUZ5',2,1339688081,'0'),(14,'334edc6accfc3bd13f95984cef10b108','pNTXtxCHLRW26bFKQVZ5aejp49eintxC',2,1339690480,'0'),(15,'334edc6accfc3bd13f95984cef10b108','Xx2w26bgKQVqvzEJejNTY38dHMRmrwBG',2,1339690775,'0'),(16,'4f8f8fadae33133d7938a94897580b82','AJq5Jp4IpDiYDSyCSxcSxLsGl2GVBgvb',6,1345229833,'0'),(17,'4f8f8fadae33133d7938a94897580b82','T3In3Hm3Hm2GWBgwbQvbQ5Kq5Jp5jYEj',6,1345237425,'0');
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_permissions`
--

DROP TABLE IF EXISTS `user_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_permissions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `resource` varchar(50) NOT NULL,
  `access` enum('deny','allow') NOT NULL DEFAULT 'deny',
  `role_id` int(10) unsigned DEFAULT NULL,
  `user_id` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `resource` (`resource`,`role_id`,`user_id`),
  KEY `role_id` (`role_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `user_permissions_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `user_roles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `user_permissions_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_permissions`
--

LOCK TABLES `user_permissions` WRITE;
/*!40000 ALTER TABLE `user_permissions` DISABLE KEYS */;
INSERT INTO `user_permissions` VALUES (3,'post','deny',3,NULL),(5,'vote','allow',2,NULL),(7,'post','allow',1,NULL),(8,'post','allow',2,NULL);
/*!40000 ALTER TABLE `user_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_roles`
--

DROP TABLE IF EXISTS `user_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_roles` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `editable` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'Not to be used by non standard entries',
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_roles`
--

LOCK TABLES `user_roles` WRITE;
/*!40000 ALTER TABLE `user_roles` DISABLE KEYS */;
INSERT INTO `user_roles` VALUES (1,'Admin',0),(2,'Member',0),(3,'Guest',0);
/*!40000 ALTER TABLE `user_roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(32) NOT NULL,
  `role_id` int(10) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `registered` int(11) unsigned NOT NULL,
  `email` varchar(255) NOT NULL,
  `lastlogin_ip` varbinary(16) NOT NULL,
  `lastlogin_time` int(11) NOT NULL,
  `permissions` tinyint(1) unsigned NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  KEY `role_id` (`role_id`),
  CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `user_roles` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (0,'anonymous','',3,'Anonymous',0,'','',0,1),(2,'asd','334edc6accfc3bd13f95984cef10b108',1,'Bob Burgers',1337777990,'bob@burgers.cn','\0\0',0,1),(3,'qwe','76d80224611fc919a5d54f0ff9fba446',1,'Carl Johnson',1337777990,'cj@grovest.com','\0\0',0,1),(6,'thehosh','4f8f8fadae33133d7938a94897580b82',2,'',1345212066,'superaktieboy@gmail.com','',0,1);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `votes`
--

DROP TABLE IF EXISTS `votes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `votes` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `post_id` int(10) unsigned NOT NULL,
  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `ip` varbinary(16) NOT NULL,
  `user_agent` varchar(255) NOT NULL,
  `vote` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1 or -1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_post` (`post_id`,`user_id`),
  KEY `post` (`post_id`),
  KEY `voter` (`user_id`),
  CONSTRAINT `votes_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `votes_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `votes`
--

LOCK TABLES `votes` WRITE;
/*!40000 ALTER TABLE `votes` DISABLE KEYS */;
/*!40000 ALTER TABLE `votes` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2012-08-18  5:23:34
