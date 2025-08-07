<?php
// Define a robust base URL for the application.
// This calculates the path from the web root to the project's directory,
// ensuring asset and page links work correctly from any subdirectory.
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'];
// Replace backslashes with forward slashes for consistency
$document_root = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']);
$project_dir = str_replace('\\', '/', __DIR__);
// Get the relative path from the document root
$project_path = str_replace($document_root, '', $project_dir);
// Ensure there's a trailing slash
define('BASE_URL', rtrim($protocol . $host . $project_path, '/') . '/');

// إعدادات Supabase
// Supabase Project Configuration
$supabase_url = "https://itjoqshxikzlktgtrxah.supabase.co";
// Public anonymous key - safe to be in client-side code
$supabase_key = "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6Iml0am9xc2h4aWt6bGt0Z3RyeGFoIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NTMxOTQzNTEsImV4cCI6MjA2ODc3MDM1MX0.272FBxuYu99dmdbYN_X0doFDTeeSmn1rFFhteQeg7Wk";
// Service role key - MUST BE KEPT SECRET ON THE SERVER
$supabase_service_key = "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6Iml0am9xc2h4aWt6bGt0Z3RyeGFoIiwicm9sZSI6InNlcnZpY2Vfcm9sZSIsImlhdCI6MTc1MzE5NDM1MSwiZXhwIjoyMDY4NzcwMzUxfQ.MW3KZOhwpnKxotu46jCoAi22tSbmSif-5NP9WUKKkeA";
