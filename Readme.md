# Mautic AI Reports Bundle

> ## ⚠️ **SECURITY WARNING - BETA SOFTWARE** ⚠️
>
> **THIS PLUGIN IS IN EARLY BETA AND POSES SIGNIFICANT SECURITY RISKS**
>
> ### DO NOT USE IN PRODUCTION
>
> This plugin allows AI to generate and execute SQL queries against your Mautic database. While it attempts to restrict queries to SELECT statements only, there are inherent risks:
>
> - **Data Exposure Risk**: The AI has access to your entire database schema and can query any table
> - **Performance Impact**: Poorly optimized AI-generated queries could overload your database
> - **Prompt Injection**: Malicious users might craft questions that bypass safety restrictions
> - **Data Leakage**: Sensitive data could be exposed through cleverly worded questions
> - **Beta Quality**: This is experimental software that has not undergone security auditing
>
> ### Recommended Usage:
> - ✅ Testing and development environments only
> - ✅ Demonstration and proof-of-concept purposes
> - ✅ Non-production databases with sample data
> - ❌ **NEVER** use on production systems with real customer data
> - ❌ **NEVER** grant access to untrusted users
>
> **You have been warned. Use at your own risk.**

---

## Overview

The Mautic AI Reports Bundle provides an experimental natural language interface for generating database reports. Users can ask questions in plain language, and the AI will generate and execute SQL queries to answer them.

**Example:**
- User: "How many contacts were created last month?"
- AI: Generates `SELECT COUNT(*) FROM leads WHERE date_added >= DATE_SUB(NOW(), INTERVAL 1 MONTH)`
- System: Executes query and displays results

## Features

- **Natural Language Queries** - Ask questions about your Mautic data in plain English
- **Automatic SQL Generation** - AI converts questions to SQL queries
- **Database Schema Awareness** - AI understands your database structure
- **Query Validation** - Only SELECT queries are allowed (INSERT/UPDATE/DELETE blocked)
- **Result Display** - Query results shown in formatted tables
- **Query Logging** - All AI-generated queries are logged for audit purposes
- **Model Selection** - Choose different AI models for query generation
- **Custom Prompts** - Configure the AI's behavior with custom instructions

## Requirements

- Mautic 4.0+ or Mautic 5.0+
- PHP 7.4 or 8.0+
- **Mautic AI Connection Bundle** (required dependency)
- A configured LiteLLM instance or OpenAI API access
- Database permissions for schema introspection

## Installation

### Via Composer

```bash
composer require mautic/ai-reports-bundle
```

This will automatically install the required `mautic/ai-connection-bundle` dependency.

### Manual Installation

1. First, install the **Mautic AI Connection Bundle** (required)
2. Download or clone this repository
3. Place the `MauticAiReportsBundle` folder in `docroot/plugins/`
4. Clear Mautic cache:
   ```bash
   php bin/console cache:clear
   ```
5. Go to Mautic Settings → Plugins
6. Click "Install/Upgrade Plugins"
7. Find "Mautic AI Reports" and publish it

## Dependencies

### Mautic AI Connection Bundle (Required)

This plugin **requires** the [Mautic AI Connection Bundle](../MauticAIconnectionBundle/README.md) to function. The AI Connection Bundle provides:
- LiteLLM service integration
- Centralized AI configuration (endpoint and API keys)
- Model management

**Important:** Configure the AI Connection Bundle first before using AI Reports!

## Configuration

Navigate to **Mautic Settings → Plugins → Mautic AI Reports** to configure the plugin.

### Settings

#### 1. AI Reports Enabled
Enable or disable the AI Reports interface. When disabled, the Reports button will not appear in the Mautic toolbar.

#### 2. AI Model
Select which AI model to use for generating SQL queries:
- GPT-4 (most capable, better at complex queries)
- GPT-3.5 Turbo (fast, cost-effective)
- Claude 3 Sonnet/Haiku/Opus
- Other models available in your LiteLLM instance

**Note:** The AI Reports and AI Console can use different models independently.

**Recommendation:** Use GPT-4 or Claude Opus for better query accuracy and safety.

#### 3. AI Report Prompt (System Instructions)

The prompt template controls how the AI interprets questions and generates SQL. This is **critical** for both functionality and security.

**Default Prompt:**
```
INSTRUCTION:
------------
You are a AI assistant for analyzing user's questions. You answer with
- A list (sql query) formatted as a HTML table
- A list (sql query) formatted as a HTML table + in combination with a graph (made with js).
you take into account the Database structure (see STRUCTURE)
you try to answer the question of the user (see USER_QUESTION)
You only output the SQL query needed to find the relevant data. nothing else, no answers, no pleasantries, nothing else.

USER_QUESTION
------------
[actual_user_question]

STRUCTURE
---------
[database_structure]
```

**Available Tokens:**
- `[actual_user_question]` - Replaced with the user's question
- `[database_structure]` - Replaced with the database schema information

**Security Considerations for Prompts:**

⚠️ Your prompt should include safety instructions:
```
IMPORTANT SECURITY RULES:
- Only generate SELECT queries
- Never use UNION to combine with other queries
- Do not include subqueries that modify data
- Limit results to reasonable numbers (use LIMIT clause)
- Do not query sensitive authentication tables
```

#### 4. Allow Graph Creation

**⚠️ WARNING:** This feature is experimental and potentially dangerous.

When enabled, the AI may attempt to generate JavaScript code for data visualization. This poses additional security risks:
- **XSS Risk**: AI-generated JavaScript could contain vulnerabilities
- **Code Injection**: Malicious prompts might generate harmful code

**Recommendation:** Keep this **DISABLED** unless you absolutely need it and understand the risks.

## Usage

### Accessing AI Reports

Once configured, access AI Reports via:
- **Reports Menu** - Navigate to the Reports section
- **Toolbar Button** - Click the AI Reports button in the top toolbar

### Asking Questions

**Example Questions:**

Simple counts:
- "How many contacts do I have?"
- "How many emails were sent last week?"
- "Count of contacts by country"

Filtering and grouping:
- "Show me contacts created in the last 30 days"
- "List the top 10 most active contacts by email opens"
- "Which campaigns have the highest click-through rates?"

Time-based analysis:
- "Email performance for March 2024"
- "Contact growth month by month this year"
- "Daily form submissions for the last week"

### Understanding Results

When you submit a question:

1. **AI generates SQL** - The query is displayed so you can review it
2. **Query is validated** - System checks it's a SELECT query
3. **Query executes** - Results are fetched from database
4. **Results display** - Data shown in a formatted table

**If something goes wrong:**
- The generated SQL is always shown (even if execution fails)
- Error messages explain what went wrong
- You can modify your question and try again

### Query Logging

All AI interactions are logged to the database for audit purposes:
- User who made the request
- Timestamp
- Original question (prompt)
- AI model used
- Generated SQL query

This audit trail helps identify misuse or security issues.

## Database Schema

The AI has access to descriptions of all tables in your Mautic database, including:

**Core Tables:**
- `leads` - Contact/Lead records
- `emails` - Email templates
- `email_stats` - Email statistics
- `campaigns` - Marketing campaigns
- `forms` - Form definitions
- `form_submissions` - Form submission data
- `pages` - Landing pages
- `page_hits` - Page visit tracking
- `segments` - Contact segments
- And many more...

The AI is provided with table names, column names, and data types to generate accurate queries.

## Security Features

### Query Restrictions

1. **SELECT-only enforcement** - Only SELECT queries are allowed at the code level
   ```php
   if (!str_starts_with($trimmedQuery, 'SELECT')) {
       throw new \Exception('Only SELECT queries are allowed');
   }
   ```

2. **No write operations** - INSERT, UPDATE, DELETE, DROP, ALTER are blocked

3. **Database user permissions** - Ensure your Mautic database user has minimal permissions (SELECT only is ideal for this feature)

### Limitations

Despite restrictions, risks remain:

❌ **What this CANNOT prevent:**
- Expensive queries that slow down your database (e.g., cartesian joins)
- Queries that expose sensitive data (e.g., "show me all user passwords")
- Queries with massive result sets that consume memory
- Cleverly worded prompts that bypass intent restrictions

✅ **What this CAN prevent:**
- Direct data modification (INSERT/UPDATE/DELETE)
- Schema changes (DROP/ALTER/CREATE)
- Multiple statements in one query

### Best Practices

1. **Use a read-only database user** for Mautic if running this plugin
2. **Restrict access** to trusted administrators only
3. **Monitor the logs** regularly for suspicious queries
4. **Test in development** thoroughly before any production use
5. **Disable when not in use** - Turn off the plugin when not actively needed
6. **Review generated queries** before trusting the results

## Troubleshooting

### "LiteLLM endpoint must be configured" error

**Solution:** Configure the **Mautic AI Connection Bundle** first with your LiteLLM endpoint and credentials.

### Generated queries are incorrect

**Solution:**
1. Try using a more capable model (GPT-4 instead of GPT-3.5)
2. Make your question more specific
3. Review the generated SQL and rephrase your question
4. Improve the prompt template with better instructions

### "Only SELECT queries are allowed" error

**Solution:** The AI generated a query that modifies data. This is blocked for security. Try rephrasing your question to ask for data rather than to change data.

### Query timeout or performance issues

**Solution:**
- Ask more specific questions that limit the data range
- Use time constraints in your questions ("last 30 days" instead of "all time")
- The AI model may need better instructions about using LIMIT clauses

### Empty results

**Solution:**
- Check if the data actually exists (try a simpler question first)
- Verify the database schema has the expected tables
- Review the generated SQL to see if it's querying the right tables

## Architecture

```
┌─────────────────────────────────────┐
│     Mautic AI Reports Bundle        │
│                                     │
│  ┌─────────────────────────────┐   │
│  │  Reports Controller         │   │
│  │  - Process questions        │   │
│  │  - Schema introspection     │   │
│  │  - SQL generation           │   │
│  │  - Query execution          │   │
│  │  - Result formatting        │   │
│  │  - Audit logging            │   │
│  └─────────────────────────────┘   │
└─────────────────────────────────────┘
              ↓ depends on
┌─────────────────────────────────────┐
│   Mautic AI Connection Bundle       │
│  ┌───────────────────────────────┐  │
│  │   LiteLLM Service             │  │
│  │  - Chat API                   │  │
│  │  - Model management           │  │
│  └───────────────────────────────┘  │
└─────────────────────────────────────┘
              ↓ connects to
┌─────────────────────────────────────┐
│       LiteLLM Proxy Server          │
│  (Routes to OpenAI, Claude, etc.)   │
└─────────────────────────────────────┘
              ↓ queries
┌─────────────────────────────────────┐
│       Mautic Database               │
│  (MySQL/MariaDB)                    │
└─────────────────────────────────────┘
```

## Development

### Testing

**NEVER test on production databases!**

Set up a development environment:
1. Clone your production database to a safe environment
2. Use sample/anonymized data
3. Test with a database user that has SELECT-only permissions
4. Try malicious prompts to test security restrictions

### Improving Query Generation

To improve AI-generated queries, you can:

1. **Enhance table descriptions** in `ReportsController.php`:
   ```php
   private function getTableDescription(string $tableName): string
   {
       $descriptions = [
           'leads' => 'Contact/Lead records with personal information',
           // Add more detailed descriptions
       ];
   }
   ```

2. **Add example queries** to your prompt template:
   ```
   EXAMPLES:
   Q: "How many contacts?"
   A: SELECT COUNT(*) FROM leads

   Q: "Top 10 countries"
   A: SELECT country, COUNT(*) as count FROM leads GROUP BY country ORDER BY count DESC LIMIT 10
   ```

3. **Use better models** - GPT-4 significantly outperforms GPT-3.5 for SQL generation

## Known Issues

1. **Complex joins** - The AI sometimes struggles with complex multi-table queries
2. **Ambiguous questions** - Vague questions may produce unexpected queries
3. **Date handling** - Date format variations can cause issues
4. **Column name guessing** - If column names don't match the question, results may be wrong
5. **No query optimization** - AI-generated queries are not optimized for performance

## Roadmap

Future improvements (if this leaves beta):
- Query result caching
- Query execution time limits
- Automatic LIMIT clause injection
- Whitelist of allowed tables
- Query complexity scoring
- Result export (CSV, Excel)
- Saved reports
- Query history and favorites
- Better error messages
- Graph visualization (when safe)

## Support

- GitHub Issues: [Report an issue](https://github.com/yourusername/mauticorangepoc/issues)
- Mautic Community: [community.mautic.org](https://community.mautic.org)
- AI Connection Bundle: [See documentation](../MauticAIconnectionBundle/README.md)

## License

GPL-3.0-or-later

## Credits

Created by Frederik Wouters and the Mautic Community

## Version

1.0.0-beta

---

> ### Final Reminder: This is Beta Software
>
> This plugin is experimental and should be treated as a proof-of-concept. Do not use it in production environments. Always review generated queries before trusting the results. Use at your own risk.
