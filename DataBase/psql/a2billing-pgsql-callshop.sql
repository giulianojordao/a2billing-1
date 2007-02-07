-- Additional tables for callshop feature
-- Copyright (c) 2006 P.Christeas <p_christeas@yahoo.com>
--

CREATE TABLE cc_agent (
    id bigserial NOT NULL PRIMARY KEY,
    name text NOT NULL,
    active boolean NOT NULL DEFAULT true,
    login VARCHAR(20) NOT NULL,
    passwd VARCHAR(40) NOT NULL,
    groupid integer,
    location text,
    datecreation timestamp without time zone DEFAULT now(),
    "language" text DEFAULT 'en'::text,
    tariffgroup integer REFERENCES cc_tariffgroup(id),
    options integer NOT NULL DEFAULT 0,
    credit NUMERIC(12,4) NOT NULL DEFAULT 0,
    climit NUMERIC(12,4) NOT NULL DEFAULT 0,
    currency CHARACTER(3) NOT NULL DEFAULT 'EUR',
    locale VARCHAR(10) DEFAULT 'C'
    );

-- one way: put the agent inside the card:
-- ALTER TABLE cc_card ADD agentid bigint REFERENCES cc_agent(id) ON DELETE RESTRICT;
-- CREATE INDEX cc_card_agent_idx ON cc_card(agentid);

-- second way: A different table
CREATE TABLE cc_agent_cards (
	card_id bigint NOT NULL PRIMARY KEY REFERENCES cc_card(id) ON DELETE CASCADE,
	agentid bigint NOT NULL REFERENCES cc_agent(id) ON DELETE RESTRICT,
	def boolean NOT NULL DEFAULT 'f') ;
	
CREATE INDEX cc_agent_cards_agent ON cc_agent_cards(agentid);

CREATE TABLE cc_booth (
	id bigserial NOT NULL PRIMARY KEY,
	name text NOT NULL,
	location text,
	agentid bigint NOT NULL REFERENCES cc_agent(id),
	datecreation timestamp without time zone DEFAULT now(),
	last_activation timestamp without time zone,
	disabled boolean NOT NULL DEFAULT 'f',
	cur_card_id bigint REFERENCES cc_card(id),
	def_card_id bigint REFERENCES cc_card(id),
	callerid TEXT

);

ALTER TABLE cc_currencies ADD csign VARCHAR(6);
ALTER TABLE cc_currencies ADD sign_pre boolean DEFAULT 'f' NOT NULL;
ALTER TABLE cc_currencies ADD cformat VARCHAR(20) DEFAULT 'FM99G999G999G990D00' NOT NULL;
	
-- This table will hold the transactions for the agent<->card
-- refills. boothid is optional.
-- 'carried' is important: if set to true, then the transaction
-- will NOT cause cc_agent.credit/cc.card.credit to be updated.
-- That is useful when inserting dummy refills to carry the credit
-- between sessions. 

CREATE TABLE cc_agentrefill (
    id bigserial NOT NULL,
    date timestamp without time zone DEFAULT now() NOT NULL,
    credit numeric(12,4) NOT NULL,
    carried boolean NOT NULL DEFAULT false,
    pay_type integer,
    card_id bigint NOT NULL REFERENCES cc_card(id),
    agentid bigint NOT NULL REFERENCES cc_agent(id),
    boothid bigint REFERENCES cc_booth(id)
);


CREATE TABLE cc_shopsessions (
	id bigserial PRIMARY KEY,
	booth bigint NOT NULL REFERENCES cc_booth(id),
	card bigint NOT NULL REFERENCES cc_card(id),
	starttime timestamp NOT NULL DEFAULT now(),
	endtime  timestamp,
	state text NOT NULL -- REFERENCES cc_texts(id)
);


CREATE TABLE cc_agentpay (
    id bigserial NOT NULL,
    date timestamp without time zone DEFAULT now() NOT NULL,
    credit numeric(12,4) NOT NULL,
    pay_type integer,
    agentid bigint NOT NULL REFERENCES cc_agent(id),
    descr text 
);

-- CREATE OR REPLACE FUNCTION booth_start(booth bigint, agent_id bigint) RETURNS bigint
-- 	AS $$
-- 		UPDATE cc_card SET activated= 't' 
-- 			FROM cc_agent, cc_booth 
-- 			WHERE cc_booth.cur_card_id= cc_card.id AND
-- 				cc_booth.id = $1 AND
-- 				cc_booth.agentid = $2;
-- 		select COUNT(cc_card.id) FROM cc_card,cc_agent, cc_booth 
-- 			WHERE cc_booth.cur_card_id= cc_card.id AND
-- 				cc_booth.id = $1 AND
-- 				cc_booth.agentid = $2;
-- 	$$ LANGUAGE SQL VOLATILE STRICT;
	
-- Charges could now come from the agents. If so, a a2b admin should better
-- confirm those.
	
ALTER TABLE cc_charge ADD agentid bigint references cc_agent(id);
ALTER TABLE cc_charge ADD from_agent boolean NOT NULL DEFAULT FALSE;
ALTER TABLE cc_charge ADD checked bigint REFERENCES cc_ui_authen(userid);

CREATE TABLE cc_texts (
	id integer NOT NULL,
	lang VARCHAR(10) NOT NULL DEFAULT 'C',
	txt text NOT NULL,
	src int NOT NULL DEFAULT 1,
	CONSTRAINT cc_texts_pkey PRIMARY KEY (id,lang) );


INSERT INTO cc_templatemail VALUES ('forgetagentpw', 'info@call-labs.com', 'Call-Labs', 'Login Information', 'Your login information is as below:

Your account is $login

Your password is $password

http://call-labs.com/A2BAgent_UI/


Kind regards,
Call Labs
', '');

-- eof