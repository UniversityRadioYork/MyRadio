BEGIN;

-- Create table to list short URLs e.g. "ury.org.uk/ern2021"
CREATE TABLE public.short_urls (
    short_url_id SERIAL PRIMARY KEY,
    slug TEXT NOT NULL,
    redirect_to TEXT NOT NULL
);

-- Allow short URLs to collect analytics
CREATE TABLE public.short_url_clicks (
    click_id BIGSERIAL PRIMARY KEY,
    short_url_id INTEGER REFERENCES public.short_urls (short_url_id) ON DELETE CASCADE,
    click_time TIMESTAMPTZ NOT NULL,
    user_agent TEXT DEFAULT NULL,
    ip_address INET DEFAULT NULL
);

COMMIT;
