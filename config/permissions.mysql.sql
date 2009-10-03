-- this script will assign the user permissions.

-- assign user rights
-- NB: this first grant also sets the user password.
use idp_development;
grant select,insert,delete on idp_association_secret to user@'%' identified by 'password';
grant select,insert,update,delete on idp_identity to user@'%';
grant select,insert,update,delete on idp_persona to user@'%';
grant select,insert,update,delete on idp_trust_root to user@'%';

grant all on idp_test.* to user@'%';

