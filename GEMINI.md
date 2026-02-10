# Gemini Guidelines - Laravel Boost

Ces instructions s'appliquent à l'agent Gemini lorsqu'il travaille sur le projet `laravel-boost`.

<laravel-boost-guidelines>

## Foundation Rules
- Use modern PHP 8.2+ features.
- Follow Laravel 11/12 naming conventions.
- Ensure strict types are used in all new PHP files.

## MCP Development Skill
### When to Apply
Activate this skill when:
- Creating MCP tools, resources, or prompts.
- Setting up MCP server routes.
- Debugging MCP connection issues.

### Basic Usage
Register MCP servers in `routes/ai.php`:
```php
use Laravel\Mcp\Facades\Mcp;
Mcp::web();
```

### Artisan Commands
- `php artisan make:mcp-tool ToolName`
- `php artisan make:mcp-resource ResourceName`
- `php artisan make:mcp-prompt PromptName`
- `php artisan make:mcp-server ServerName`

### Common Pitfalls
- Do NOT run `mcp:start` manually (it hangs).
- Always use `search-docs` for up-to-date MCP patterns.
- Ensure MCP servers are registered in `routes/ai.php`.

</laravel-boost-guidelines>
