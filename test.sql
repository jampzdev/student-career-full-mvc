/*
SQLyog Ultimate v11.11 (64 bit)
MySQL - 5.7.8-rc : Database - psslai_jeff
*********************************************************************
*/

/*!40101 SET NAMES utf8 */;

/*!40101 SET SQL_MODE=''*/;

/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
/* Trigger structure for table `tbl_collection_atm1` */

DELIMITER $$

/*!50003 CREATE */ /*!50017 DEFINER = 'root'@'localhost' */ /*!50003 TRIGGER `tr_CollAtm1` BEFORE INSERT ON `tbl_collection_atm1` FOR EACH ROW BEGIN
	set New.amount_collected = checkCurrency(cleanString(New.amount_collected));
	
    END */$$


DELIMITER ;

/* Trigger structure for table `tbl_collection_atm2` */

DELIMITER $$

/*!50003 CREATE */ /*!50017 DEFINER = 'root'@'localhost' */ /*!50003 TRIGGER `tr_CollAtm2` BEFORE INSERT ON `tbl_collection_atm2` FOR EACH ROW BEGIN
	
	SET New.first_name = makeAlphaNum(New.first_name);
	SET New.middle_name = makeAlphaNum(New.middle_name);
	SET New.last_name = makeAlphaNum(New.last_name);
	SET New.hold_amort = checkCurrency(makeAlphaNum(New.hold_amort));
    END */$$


DELIMITER ;

/* Trigger structure for table `tbl_collection_bfp_re` */

DELIMITER $$

/*!50003 CREATE */ /*!50017 DEFINER = 'root'@'localhost' */ /*!50003 TRIGGER `tr_CollBfpAc` BEFORE INSERT ON `tbl_collection_bfp_re` FOR EACH ROW BEGIN
	
	SET New.MonthlyAmort = checkCurrency(makeAlphaNum(New.MonthlyAmort));
    END */$$


DELIMITER ;

/* Trigger structure for table `tbl_collection_bjmp_ac` */

DELIMITER $$

/*!50003 CREATE */ /*!50017 DEFINER = 'root'@'localhost' */ /*!50003 TRIGGER `tr_CollBjmpAc` BEFORE INSERT ON `tbl_collection_bjmp_ac` FOR EACH ROW BEGIN
	
	SET New.first_name = makeAlphaNum(New.first_name);
	SET New.middle_name = makeAlphaNum(New.middle_name);
	SET New.last_name = makeAlphaNum(New.last_name);
	SET New.payment = checkCurrency(makeAlphaNum(New.payment));
    END */$$


DELIMITER ;

/* Trigger structure for table `tbl_collection_bjmp_re` */

DELIMITER $$

/*!50003 CREATE */ /*!50017 DEFINER = 'root'@'localhost' */ /*!50003 TRIGGER `tr_CollBjmpRe` BEFORE INSERT ON `tbl_collection_bjmp_re` FOR EACH ROW BEGIN
	
	SET New.first_name = makeAlphaNum(New.first_name);
	SET New.last_name = makeAlphaNum(New.last_name);
	SET New.middle_name = makeAlphaNum(New.middle_name);
	SET New.payment = checkCurrency(makeAlphaNum(New.payment));
    END */$$


DELIMITER ;

/* Trigger structure for table `tbl_collection_pnp_ac` */

DELIMITER $$

/*!50003 CREATE */ /*!50017 DEFINER = 'root'@'localhost' */ /*!50003 TRIGGER `tr_CollPnpAC` BEFORE INSERT ON `tbl_collection_pnp_ac` FOR EACH ROW BEGIN
	Set New.last_name = checkNames(makeAlphaNum(New.last_name));
	SET New.first_name = checkNames(makeAlphaNum(New.first_name));
	SET New.middle_name = checkNames(makeAlphaNum(New.last_name));
	set New.amount = checkCurrency(cleanString(New.amount));
    END */$$


DELIMITER ;

/* Trigger structure for table `tbl_collection_pnp_re` */

DELIMITER $$

/*!50003 CREATE */ /*!50017 DEFINER = 'root'@'localhost' */ /*!50003 TRIGGER `tr_CollPnpRe` BEFORE INSERT ON `tbl_collection_pnp_re` FOR EACH ROW BEGIN
	set New.monthly_amort = checkCurrency(cleanString(New.monthly_amort));
	SET New.retire_name = checkNames(makeAlphaNum(New.retire_name));
    END */$$


DELIMITER ;

/* Trigger structure for table `tbl_collection_ppsc` */

DELIMITER $$

/*!50003 CREATE */ /*!50017 DEFINER = 'root'@'localhost' */ /*!50003 TRIGGER `tr_CollPpsc` BEFORE INSERT ON `tbl_collection_ppsc` FOR EACH ROW BEGIN
	
	SET New.first_name = makeAlphaNum(New.first_name);
	SET New.last_name = makeAlphaNum(New.last_name);
	SET New.middle_name = makeAlphaNum(New.middle_name);
	SET New.contribution_amount = checkCurrency(makeAlphaNum(New.contribution_amount));
	SET New.loan_amount = checkCurrency(makeAlphaNum(New.loan_amount));
	SET New.total_amount = checkCurrency(makeAlphaNum(New.total_amount));
    END */$$


DELIMITER ;

/* Trigger structure for table `tbl_loan_atm_file` */

DELIMITER $$

/*!50003 CREATE */ /*!50017 DEFINER = 'root'@'localhost' */ /*!50003 TRIGGER `tr_LoanAtm` BEFORE INSERT ON `tbl_loan_atm_file` FOR EACH ROW BEGIN
	SET New.lproNo = makeAlphaNum(New.lproNo);
	SET New.pinAcctNo = makeAlphaNum(New.pinAcctNo);
	SET New.penAcctNo = makeAlphaNum(New.penAcctNo);
	SET New.fName = makeAlphaNum(New.fName);
	SET New.mName = makeAlphaNum(New.mName);
	SET New.lName = makeAlphaNum(New.lName);
	SET New.MOA1 = checkCurrency(makeAlphaNum(New.MOA1));
	SET New.MOA2 = checkCurrency(makeAlphaNum(New.MOA2));
	SET New.loanAmt = checkCurrency(makeAlphaNum(New.loanAmt));
    END */$$


DELIMITER ;

/* Trigger structure for table `tbl_loan_csv_file` */

DELIMITER $$

/*!50003 CREATE */ /*!50017 DEFINER = 'root'@'localhost' */ /*!50003 TRIGGER `tr_LoanBilling` BEFORE INSERT ON `tbl_loan_csv_file` FOR EACH ROW BEGIN
	SET New.lproNo = makeAlphaNum(New.lproNo);
	SET New.pinAcctNo = makeAlphaNum(New.pinAcctNo);
	SET New.penAcctNo = makeAlphaNum(New.penAcctNo);
	SET New.fName = makeAlphaNum(New.fName);
	SET New.mName = makeAlphaNum(New.mName);
	SET New.lName = makeAlphaNum(New.lName);
	SET New.loanAmt = makeAlphaNum(New.loanAmt);
	SET New.MOA1 = makeAlphaNum(New.MOA1);
	SET New.MOA2 = makeAlphaNum(New.MOA2);
	SET New.lproStatus = makeAlphaNum(New.lproStatus);
	SET New.timeGrant = makeAlphaNum(New.timeGrant);
		SET New.MOA1 = checkCurrency(makeAlphaNum(New.MOA1));
	SET New.MOA2 = checkCurrency(makeAlphaNum(New.MOA2));
	SET New.loanAmt = checkCurrency(makeAlphaNum(New.loanAmt));
    END */$$


DELIMITER ;

/* Trigger structure for table `tbl_member_account_file` */

DELIMITER $$

/*!50003 CREATE */ /*!50017 DEFINER = 'root'@'localhost' */ /*!50003 TRIGGER `tr_MemAcc` BEFORE INSERT ON `tbl_member_account_file` FOR EACH ROW BEGIN
    	SET New.MemberNo = makeAlphaNum(CleanString(New.MemberNo));
    	SET New.TSAccNo = makeAlphaNum(CleanString(New.TSAccNo));
    	SET New.AccountName = makeAlphaNum(CleanString(New.AccountName));
	SET New.AccntStat = makeAlphaNum(CleanString(New.AccntStat));
    END */$$


DELIMITER ;

/* Trigger structure for table `tbl_member_info_file` */

DELIMITER $$

/*!50003 CREATE */ /*!50017 DEFINER = 'root'@'localhost' */ /*!50003 TRIGGER `tr_MemberInfo` BEFORE INSERT ON `tbl_member_info_file` FOR EACH ROW BEGIN
	SET New.FirstName = checkNames(makeAlphaNum(New.FirstName));
	SET New.LastName = checkNames(makeAlphaNum(New.LastName));
	SET New.MiddleName =checkNames(makeAlphaNum(New.MiddleName));
	SET New.PINAcctNo = CONVERT(CONVERT(New.PINAcctNo USING binary) USING utf8);
	SET New.SvcStat = makeAlphaNum(New.SvcStat);
    END */$$


DELIMITER ;

/* Function  structure for function  `checkCurrency` */

DELIMITER $$

/*!50003 CREATE DEFINER=`root`@`localhost` FUNCTION `checkCurrency`(`in_str` TEXT) RETURNS text CHARSET utf8
BEGIN
      DECLARE out_str TEXT DEFAULT ''; 
      IF ISNULL(in_str) THEN
            RETURN NULL; 
      ELSE
	set out_str = REPLACE(in_str,',','');
      END IF; 
      RETURN out_str; 
END */$$
DELIMITER ;

/* Function  structure for function  `checkNames` */

DELIMITER $$

/*!50003 CREATE DEFINER=`root`@`localhost` FUNCTION `checkNames`(`in_str` TEXT) RETURNS text CHARSET utf8
BEGIN
      DECLARE out_str TEXT DEFAULT ''; 
      IF ISNULL(in_str) THEN
            RETURN NULL; 
      ELSE
	set out_str = REPLACE(in_str,'?','Ñ');
      END IF; 
      RETURN out_str; 
END */$$
DELIMITER ;

/* Function  structure for function  `cleanString` */

DELIMITER $$

/*!50003 CREATE DEFINER=`root`@`localhost` FUNCTION `cleanString`(`in_str` text) RETURNS text CHARSET utf8
BEGIN
/** 
 * Function will strip all non-ASCII and unwanted ASCII characters in string 
 * 
 * @author Sunny Attwal 
 * 
 * @param text in_arg 
 * @return text
 */ 
      DECLARE out_str text DEFAULT ''; 
      IF ISNULL(in_str) THEN
            RETURN NULL; 
      ELSE
	set out_str = REPLACE(REPLACE(TRIM(in_str),CHAR(9),''),'  ','');
      END IF; 
      RETURN out_str; 
END */$$
DELIMITER ;

/* Function  structure for function  `makeAlphaNum` */

DELIMITER $$

/*!50003 CREATE DEFINER=`root`@`localhost` FUNCTION `makeAlphaNum`(`in_str` text) RETURNS text CHARSET utf8
BEGIN
      DECLARE out_str text DEFAULT ''; 
      DECLARE c text DEFAULT ''; 
      DECLARE pointer INT DEFAULT 1; 
      IF ISNULL(in_str) THEN
            RETURN NULL; 
      ELSE
            WHILE pointer <= LENGTH(in_str) DO 
                  SET c = MID(in_str, pointer, 1); 
                  IF c REGEXP '[^a-zA-Z0-9@:. \'\-`,\&]' THEN
		    SET out_str = CONCAT(out_str, '');   
                  ELSE
		    SET out_str = CONCAT(out_str, c); 
                  END IF; 
                  SET pointer = pointer + 1; 
            END WHILE; 
      END IF; 
      RETURN out_str; 
END */$$
DELIMITER ;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
