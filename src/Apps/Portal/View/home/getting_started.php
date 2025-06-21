<?php

use League\Plates\Template\Template;
use Slim\Http\ServerRequest;
use XAKEPEHOK\Lokilizer\Apps\Portal\Components\RouteUri;

/** @var Template $this */
/** @var ServerRequest $request */
/** @var RouteUri $route */

$this->layout('project_layout', ['request' => $request, 'title' => 'Getting started']);
?>

<ol>
    <li>
        <span class="badge text-bg-warning">Required</span>
        <a href="<?=$route('llm')?>">Configure LLM endpoints</a> in <code>⚙️ Settings -> 🧠 LLM endpoints</code>.
        Set ChatGPT/Deepseek token, proxy (in needed) or add another OpenAI API compatible LLM
    </li>
    <li>
        <span class="badge text-bg-warning">Required</span>
        <a href="<?=$route('settings')?>">Set default LLM</a> in <code>⚙️ Settings -> ⚙️ Project settings</code>
    </li>
    <li>
        <span class="badge text-bg-warning">Required</span>
        <a href="<?=$route('glossary/common')?>">Create common glossary</a> in <code>📜 Glossary -> 📗 Common</code>.
        Describe your application summary and add app-specific terminology. You can fill glossary in your primary language
        only (or with secondary) and save it. After that, you can click on <code>Add language button</code> for automatic
        translation to selected language
    </li>
    <li>
        <span class="badge text-bg-secondary">Optional</span>
        <a href="<?=$route('users')?>">Invite users</a> in <code>⚙️ Settings -> 👥 Users</code>. You can choose
        different roles for different users
    </li>
    <li>
        <span class="badge text-bg-warning">Required</span>
        <a href="<?=$route('upload')?>">Upload</a> translation in <code>🏭 Batch -> 📤 Upload translation</code>.
        At first, you need to upload primary language, at second, you need to upload secondaries languages.
    </li>
    <li>
        <span class="badge text-bg-secondary">Optional</span>
        After upload existed translations analyze <a href="<?=$route('groups')?>">groups</a>,
        <a href="<?=$route('duplicates')?>">duplicates</a> and <a href="<?=$route('loosed-placeholders')?>">loosed placeholders</a>
        in <code>🛠️ Tools</code> menu - it can help you normalize and remove duplicates. These tools are just help you
        analyze data. Normalization and duplicates removing you should do separately in you code writing tool (IDE). After that,
        <a href="<?=$route('upload')?>">upload</a> normalized translation in <code>🏭 Batch -> 📤 Upload translation</code>.
        Primary language at first, secondaries at second.
    </li>
    <li>
        <span class="badge text-bg-info">Add new language</span>
        <a href="<?=$route('upload')?>">Upload</a> translation in new language or add new language in
        <a href="<?=$route('glossary/common')?>">glossary</a>. After that, you can run
        <a href="<?=$route('batch/translate')?>">AI translate</a> in <code>🏭 Batch -> 🔤 AI Translate</code>
    </li>
</ol>