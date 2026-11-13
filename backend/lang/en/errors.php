<?php

// Same keys as lang/es/errors.php.
return [
    'validation_failed' => 'Some fields are not valid.',
    'duplicate_project' => 'The repository :repository is already being tracked.',
    'project_not_found' => 'Project :id does not exist.',
    'route_not_found' => 'Route not found.',
    'repository_not_accessible' => 'GitHub cannot find :resource or the token has no access to it.',
    'missing_token' => 'GITHUB_TOKEN is missing: set it in the server .env file.',
    'connection_failed' => 'Cannot connect to GitHub: :detail',
    'token_rejected' => 'GitHub rejected the token (401). Check GITHUB_TOKEN.',
    'rate_limited' => 'GitHub rate limit exceeded; it will retry on the next sync.',
    'unexpected_response' => 'GitHub responded :status when reading :resource: :detail',
    'legacy' => ':text',

    'auth' => [
        'unauthenticated' => 'Sign in to continue.',
        'second_factor_expired' => 'The sign-in has expired; enter your password again.',
        'setup_expired' => 'The setup has expired; start again.',
        'invalid_password' => 'Wrong password.',
        'invalid_code' => 'Wrong or already used code.',
        'invalid_setup_code' => 'Wrong setup code. Look for it in the backend logs.',
        'too_many_attempts' => 'Too many attempts. Wait :seconds s and try again.',
        'setup_not_available' => 'The account already exists.',
        'setup_required' => 'There is no account yet: complete the initial setup.',
        'csrf_mismatch' => 'The session has changed; reload the page.',
    ],

    'resource' => [
        'repository' => 'the repository :repository',
        'repositories' => 'your repositories',
        'branch' => 'the branch :branch of :repository',
        'blob' => 'the file :sha of :repository',
        'pulls' => 'the PRs of :repository',
        'pull' => 'PR #:number of :repository',
    ],

    'validation' => [
        'repository_format' => 'The repository must look like "owner/name".',
        'branch_invalid' => 'Invalid branch name.',
        'paths_required' => 'Enter at least one path or glob, e.g. docs/**/*.md.',
        'name_too_long' => 'The name cannot be longer than 100 characters.',
        'password_too_short' => 'The password must be at least :min characters long.',
        'password_mismatch' => 'The passwords do not match.',
    ],

    'request' => [
        'required' => 'The :attribute field is required.',
        'paths.min' => 'Enter at least one path or glob, e.g. docs/**/*.md.',
        'array' => 'The :attribute field must be a list.',
        'string' => 'The :attribute field must be text.',
        'max' => 'The :attribute field is too long.',
        'prohibited' => 'The :attribute cannot be changed; add another project instead.',
    ],

    'attributes' => [
        'repository' => 'repository',
        'branch' => 'branch',
        'paths' => 'paths',
        'name' => 'name',
    ],
];
