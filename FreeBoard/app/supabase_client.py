from supabase import create_client, Client
from app.config import settings

# Server-side only — uses Service Role Key (never exposed to browser)
supabase: Client = create_client(
    settings.supabase_url,
    settings.supabase_service_role_key,
)
