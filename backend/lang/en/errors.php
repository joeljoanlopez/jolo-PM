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
        'group_by_invalid' => 'Group by "file" or by "section".',
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
        'groupBy' => 'group by',
    ],
];
