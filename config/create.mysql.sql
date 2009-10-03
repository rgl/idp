-- this script creates the database and sets the user permissions.
-- NB: make sure you activate the InnoDB engine on MySQL!

create database idp_development character set utf8 collate utf8_general_ci;
create database idp_test character set utf8 collate utf8_general_ci;
-- You MUST use an UTF8 character set!
-- You SHOULD use a sane collation
-- TIP: you can browse your MySQL collations with:
--         show collation like '%utf%';
