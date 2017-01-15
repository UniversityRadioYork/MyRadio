INSERT INTO myury.api_key (key_string, description, revoked) VALUES ('travis-test-key', 'Travis test key', FALSE);
INSERT INTO public.l_action (descr, phpconstant) VALUES ('API sudo', 'AUTH_APISUDO');
INSERT INTO myury.api_key_auth (key_string, typeid) SELECT 'travis-test-key' AS key_string, typeid FROM public.l_action WHERE phpconstant='AUTH_APISUDO';
