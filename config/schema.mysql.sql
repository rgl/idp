-- = TODO
-- * Use varchar instead of char?
--   See the TIP at http://mysql.org/doc/refman/5.0/en/charset-unicode.html


-- = Reference
-- * http://mysql.org/doc/refman/5.0/en/sql-syntax.html
-- * http://mysql.org/doc/refman/5.0/en/create-table.html
-- * http://mysql.org/doc/refman/5.0/en/alter-table.html
-- * http://mysql.org/doc/refman/5.0/en/create-index.html
-- * http://mysql.org/doc/refman/5.0/en/data-types.html
-- * http://mysql.org/doc/refman/5.0/en/innodb-foreign-key-constraints.html
-- * http://mysql.org/doc/refman/5.0/en/example-auto-increment.html
-- * http://mysql.org/doc/refman/5.0/en/innodb-auto-increment-column.html
-- * http://mysql.org/doc/refman/5.0/en/string-functions.html
-- * http://mysql.org/doc/refman/5.0/en/grant.html

-- = MySQL caveats
-- * Foreign keys cannot be declared in the column definition.

-- maintains the secrets used in the associations between a Consumer and
-- this IdP.
-- NB: several Consumers can share the same secret (but each association
--     has it own salt added to this).
create table idp_association_secret (
	id int unsigned auto_increment primary key,
	-- shared secret
	secret char(64) character set ascii not null,
	-- time in seconds (from UNIX epoc) at which this association MUST
	-- expire.
	expires_at int not null
) engine=innodb;
-- TIP: see current column sizes with:
--         select length(secret) from idp_association_secret;
create index idp_association_secret_expires on idp_association_secret(expires_at);


-- contains all identities and associated user.
create table idp_identity (
	-- XXX there is a big caveat with an auto_increment on a InnoDB
	--     table.  MySQL does not store the counter on disk... after
	--     each restart the counter is reconstructed using MAX(id) :|
	--     This is troublesome, because if we are not carefull,
	--     orphaned foreign rows can be inherited after we create a new
	--     row in this table...
	id int unsigned auto_increment primary key,
	disabled boolean default true,
	identity varchar(255) not null unique,
	username varchar(255) not null unique,
	-- NB: the constraint is defined after the idp_persona table
	--     definition.
	default_persona_id int unsigned null default null
) engine=innodb;


-- contains all the personas of a given identity.
create table idp_persona (
	id int unsigned auto_increment primary key,
	identity_id int unsigned not null,
	name varchar(64) not null,
	sr_nickname varchar(64) null default null,
	sr_email varchar(255) null default null,
	sr_fullname varchar(128) null default null,
	sr_dob_year int null default null,
	sr_dob_month int null default null,
	sr_dob_day int null default null,
	sr_gender enum('M', 'F') null default null,
	sr_postalcode varchar(128) null default null,
	sr_country varchar(64) null default null,
	sr_language varchar(64) null default null,
	sr_timezone varchar(64) null default null,
	-- foreign key to idp_identity.  if the referenced (idp_identity)
	-- tuple is deleted, this tuple is also deleted.
	constraint idp_fk_persona_identity_id foreign key (identity_id) references idp_identity(id) on delete cascade,
	constraint idp_unq_persona unique (identity_id, name)
) engine=innodb;


-- add the constraint to the identity table.
-- foreign key to idp_persona.  if the referenced (idp_persona)
-- tuple is deleted, this tuple is reset to NULL.
alter table idp_identity add constraint idp_fk_identity_default_persona_id foreign key (default_persona_id) references idp_persona(id) on delete set null;


-- contains all the trusted sites of a given identity.
create table idp_trust_root (
	id int unsigned auto_increment not null primary key,
	trust_root varchar(255) not null,
	auto_approve boolean not null,
	approve_count int unsigned not null,
	identity_id int unsigned not null,
	persona_id int unsigned null default null,
	constraint idp_unq_trust_root unique (identity_id, trust_root),
	-- foreign key to idp_identity.  if the referenced (idp_identity)
	-- tuple is deleted, this tuple is also deleted.
	constraint idp_fk_trust_root_identity_id foreign key (identity_id) references idp_identity(id) on delete cascade,
	-- foreign key to idp_persona.  if the referenced (idp_persona)
	-- tuple is deleted, this field is set to null.
	constraint idp_fk_trust_root_persona_id foreign key (persona_id) references idp_persona(id) on delete set null
) engine=innodb;

