-- fixtures needed for running the tests.
insert into idp_identity(identity, username, disabled)
	values(
		'http://localhost/prototype/id.php/test-alice',
		'test-alice',
		false
	);
insert into idp_trust_root(trust_root, auto_approve, approve_count, identity_id)
	values(
		'http://trust-once.consumer.example/',
		0,
		0,
		(select id from idp_identity where username='test-alice')
	);


insert into idp_identity(identity, username, disabled)
	values(
		'http://localhost/prototype/id.php/test-approve-always',
		'test-approve-always',
		false
	);
insert into idp_trust_root(trust_root, auto_approve, approve_count, identity_id)
	values(
		'http://localhost/wordpress/',
		1,
		0,
		(select id from idp_identity where username='test-approve-always')
	);


insert into idp_identity(identity, username, disabled)
	values(
		'http://localhost/prototype/id.php/test-approve-once',
		'test-approve-once',
		false
	);
insert into idp_trust_root(trust_root, auto_approve, approve_count, identity_id)
	values(
		'http://localhost/wordpress/',
		1,
		0,
		(select id from idp_identity where username='test-approve-once')
	);


-- identity with a persona
insert into idp_identity(identity, username, disabled)
	values(
		'http://localhost/prototype/id.php/test-janedoe',
		'test-janedoe',
		false
	);
insert into idp_persona(name, identity_id, sr_fullname, sr_nickname, sr_email, sr_country, sr_language)
	values(
		'Public',
		(select id from idp_identity where username='test-janedoe'),
		'Jane Doe',
		'janedoe',
		'jane.doe@example',
		'US',
		'en'
	);
insert into idp_persona(name, identity_id, sr_fullname, sr_nickname, sr_email, sr_country, sr_language)
	values(
		'Work',
		(select id from idp_identity where username='test-janedoe'),
		'Dr. Jane Doe',
		'janedoe',
		'jane.doe@work.example',
		'US',
		'en'
	);
insert into idp_trust_root(trust_root, auto_approve, approve_count, identity_id, persona_id)
	values(
		'http://localhost/wordpress',
		0,
		0,
		(select id from idp_identity where username='test-janedoe'),
		(select p.id from idp_identity as i inner join idp_persona as p
			on i.id=p.identity_id
			where i.username='test-janedoe' and p.name='Public')
	);
insert into idp_persona(name, identity_id, sr_fullname, sr_nickname, sr_email, sr_country, sr_language)
	values(
		'Blogsphere',
		(select id from idp_identity where username='test-janedoe'),
		'Raging Jane Doe',
		'janedoe',
		'jane.doe@my.blog.example',
		'US',
		'en'
	);


/*
-- display all identities, and their trusted sites.
select i.id, i.username, t.trust_root, t.auto_approve, t.approve_count
from
	idp_identity as i inner join idp_trust_root as t
	on (i.id = t.identity_id);


-- display all identities, their trusted sites, and respective persona (if associated).
select i.id, i.username, t.trust_root, t.auto_approve, t.approve_count, p.name as 'Persona', p.sr_fullname, p.sr_email
from
	idp_identity as i inner join idp_trust_root as t
	on (i.id = t.identity_id)
	left outer join idp_persona as p
	on (t.persona_id = p.id);

*/
