# Host: localhost  (Version: 5.7.26)
# Date: 2024-04-11 16:15:26
# Generator: MySQL-Front 5.3  (Build 4.234)

/*!40101 SET NAMES utf8 */;

#
# Structure for table "chat_record"
#

CREATE TABLE `chat_record` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `chatSessionId` int(11) DEFAULT NULL COMMENT '会话总id',
  `userId` int(11) DEFAULT NULL COMMENT '用户id',
  `storyId` int(11) DEFAULT NULL COMMENT '对应的故事id',
  `roleId` int(11) DEFAULT NULL COMMENT '角色的系统内部编号',
  `createAt` int(11) DEFAULT NULL,
  `pairChatRecordId` int(11) DEFAULT NULL COMMENT 'role对应的model输出记录id，model对应的role输入记录id',
  PRIMARY KEY (`id`),
  KEY `chatSessionId与storyId组合索引` (`chatSessionId`,`storyId`),
  KEY `storyId索引` (`storyId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

#
# Structure for table "chat_record_content"
#

CREATE TABLE `chat_record_content` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `chatRecordId` int(11) DEFAULT NULL COMMENT '对应的对话记录id',
  `content` text,
  `version` int(11) DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `chatId唯一索引` (`chatRecordId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

#
# Structure for table "chat_session"
#

CREATE TABLE `chat_session` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userId` int(11) DEFAULT NULL,
  `storyId` int(11) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

#
# Structure for table "story"
#

CREATE TABLE `story` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userId` int(11) DEFAULT NULL COMMENT '用户id',
  `title` varchar(255) DEFAULT NULL COMMENT '故事名称',
  `backgroundInfo` varchar(1000) DEFAULT NULL,
  `createAt` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

#
# Structure for table "story_character"
#

CREATE TABLE `story_character` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `storyId` int(11) DEFAULT NULL COMMENT '对应的故事id',
  `name` varchar(20) DEFAULT NULL COMMENT '角色的姓名',
  `feature` varchar(400) DEFAULT NULL COMMENT '角色特征',
  `avatar` varchar(255) DEFAULT NULL COMMENT '头像url',
  PRIMARY KEY (`id`),
  KEY `故事id索引` (`storyId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

#
# Structure for table "user"
#

CREATE TABLE `user` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `accessToken` varchar(10) DEFAULT NULL COMMENT '用户的校验token',
  PRIMARY KEY (`id`),
  UNIQUE KEY `用户token索引` (`accessToken`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
