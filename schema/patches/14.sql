BEGIN;
CREATE TABLE public.calendar_tokens (
                                        tokenid SERIAL PRIMARY KEY,
                                        memberid INTEGER NOT NULL REFERENCES public.member(memberid),
                                        token_str TEXT NOT NULL UNIQUE,
                                        revoked BOOLEAN DEFAULT 'f'
);
UPDATE myradio.schema
SET value = 14
WHERE attr='version';
COMMIT;
