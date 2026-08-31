<?php

namespace App\Agents;

use Illuminate\Support\Str;
use Prism\Prism\Enums\Provider;
use Prism\Prism\Facades\Prism;
use Smalot\PdfParser\Parser;

class DocumentSummaryAgent
{
    private const SYSTEM_PROMPT = <<<'PROMPT'
Eres un Agente Autónomo Especialista en Análisis Documental.
Tu objetivo es analizar el PDF provisto, comprender su estructura y contenido clave
y hacer un resumen del mismo.

IMPORTANTE: Responde ÚNICAMENTE con el resumen del documento.
NO incluyas saludos, presentaciones, ni texto introductorio.
NO incluyas frases como "Como agente autónomo..." o "He procesado el texto...".
NO incluyas despedidas ni texto adicional.
Simplemente escribe el resumen directamente.
PROMPT;

    public function __construct(private readonly Parser $parser)
    {
    }

    /**
     * Extract the text of the PDF and generate a summary using Gemini.
     */
    public function summarize(string $pdfPath): string
    {
        $text = $this->parser->parseFile($pdfPath)->getText();

        $response = Prism::text()
            ->using(Provider::Gemini, config('services.gemini.model'))
            ->withSystemPrompt(self::SYSTEM_PROMPT)
            ->withPrompt("Genera ÚNICAMENTE el resumen del siguiente documento. Sin preámbulos, sin introducciones. Solo el resumen:\n\n".$text)
            //->withMaxTokens(300)
            ->withClientOptions(['timeout' => 45])
            ->asText();

        return $response->text;
    }
}
