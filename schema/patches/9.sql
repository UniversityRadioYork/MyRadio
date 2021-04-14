BEGIN;

CREATE TABLE public.calendar_tokens (
   tokenid SERIAL PRIMARY KEY,
   memberid INTEGER NOT NULL REFERENCES public.member(memberid),
   token_str TEXT NOT NULL UNIQUE,
   revoked BOOLEAN DEFAULT 'f'
);

COMMIT;
