<?php

return [
    /*
    | When true, saving site name / URL / timezone in admin (general settings) also
    | updates the corresponding APP_NAME, APP_URL, and APP_TIMEZONE lines in the local
    | .env file (if the file is writable). The database remains the source of truth at
    | runtime; this keeps CLI and deploy tooling in sync. Disable in environments where
    | .env must not be modified from the app.
    */
    'sync_env_on_save' => (bool) env('SETTINGS_SYNC_ENV', true),
];
