<?php

namespace XAKEPEHOK\Lokilizer\Apps\Mcp;

use Mcp\Server;
use Mcp\Server\Session\Psr16SessionStore;
use Psr\Container\ContainerInterface;
use Symfony\Component\Cache\Psr16Cache;
use Symfony\Contracts\Cache\CacheInterface;
use XAKEPEHOK\Lokilizer\Components\Current;

class McpServerFactory
{

    public function __construct(
        private readonly ContainerInterface $container,
        private readonly CacheInterface $cache,
        private readonly LocalizationTools $tools,
    )
    {
    }

    public function create(): Server
    {
        $project = Current::getProject();

        return Server::builder()
            ->setServerInfo("UI terminology: {$project->getName()}", '1.0.0')
            ->setInstructions(
                'This is the authoritative read-only source of UI terminology and existing interface translations ' .
                'for this application. Do not invent or literally translate established interface labels, menu items, ' .
                'buttons, or other UI terms. The application has a primary and an optional secondary reference language; ' .
                'other supported languages are additional interface translations. Call get_languages once per session, ' .
                'then determine the working language from the content being interpreted or produced. Before working ' .
                'with content that may contain UI terminology, call get_glossaries unless the relevant glossary is ' .
                'already available in the conversation. The primary glossary contains application-wide terminology; ' .
                'primary in its name refers to its scope, not the primary language. Special glossaries contain local ' .
                'terminology for specific features or pages; use each only in the context identified by its keyPrefix ' .
                'and summary. Use glossaries in both directions: map terms from the working language to primary to ' .
                'understand content and search primary-language sources, and map primary terms to the working language ' .
                'when producing content. Use them to distinguish established product terminology from ordinary words. ' .
                'These principles apply to any task involving application UI terminology, including customer support, ' .
                'knowledge-base creation and maintenance, documentation, and content localization. Use ' .
                'search_ui_strings when the glossary lacks the term or enough context, or the task or content mentions ' .
                'specific menu items, navigation paths, or sequential steps. Read primary-language comments and reuse ' .
                'established terminology only when it fits the context; do not copy translations of merely similar ' .
                'strings wholesale.'
            )
            ->setContainer($this->container)
            ->setSession(new Psr16SessionStore(
                new Psr16Cache($this->cache),
                "mcp-{$project->id()}-",
                3600
            ))
            ->addTool(
                [$this->tools, 'getLanguages'],
                'get_languages',
                description: 'List supported UI languages as ISO 639-1 codes and identify the primary and optional secondary languages.'
            )
            ->addTool(
                [$this->tools, 'getGlossaries'],
                'get_glossaries',
                description: 'Get the application-wide primary glossary and contextual special glossaries. Use special glossaries only for the features or pages identified by keyPrefix and summary. Results include primary, optional secondary, and the working language requested via language.'
            )
            ->addTool(
                [$this->tools, 'searchRecords'],
                'search_ui_strings',
                description: 'Search current UI strings by a case-insensitive substring. Returns up to 50 unique records: whole-query substring matches come first, followed by trigram fuzzy matches sorted by similarity to tolerate typos and omitted words. Omit searchLanguage to search all languages. Each record includes its key, primary-language comment, and values for primary, optional secondary, searchLanguage and resultLanguage when provided.'
            )
            ->build();
    }

}
