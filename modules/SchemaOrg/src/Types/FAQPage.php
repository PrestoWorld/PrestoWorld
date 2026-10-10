<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\SchemaOrg\Types;

use PrestoWorld\Modules\SchemaOrg\Schema;

class FAQPage extends Schema
{
    public function __construct()
    {
        parent::__construct('FAQPage');
    }

    /**
     * Set the questions and answers.
     * @param array $faqItems Each item: ['question' => string, 'answer' => string]
     */
    public function setFaqItems(array $faqItems): void
    {
        $mainEntity = [];
        foreach ($faqItems as $item) {
            if (!isset($item['question']) || !isset($item['answer'])) {
                throw new \InvalidArgumentException('Each FAQ item must have a question and answer.');
            }
            $mainEntity[] = [
                '@type' => 'Question',
                'name' => $item['question'],
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => $item['answer'],
                ],
            ];
        }
        $this->set('mainEntity', $mainEntity);
    }

    protected function validate(): void
    {
        $mainEntity = $this->get('mainEntity');
        if (!$mainEntity || !is_array($mainEntity) || count($mainEntity) === 0) {
            throw new \InvalidArgumentException('FAQPage schema requires at least one question in mainEntity.');
        }
        foreach ($mainEntity as $index => $question) {
            if (!isset($question['@type']) || $question['@type'] !== 'Question') {
                throw new \InvalidArgumentException("Each mainEntity item must have @type set to 'Question'.");
            }
            if (!isset($question['name']) || !is_string($question['name'])) {
                throw new \InvalidArgumentException("Each question must have a string name.");
            }
            if (!isset($question['acceptedAnswer']) || !is_array($question['acceptedAnswer'])) {
                throw new \InvalidArgumentException("Each question must have an acceptedAnswer array.");
            }
            $answer = $question['acceptedAnswer'];
            if (!isset($answer['@type']) || $answer['@type'] !== 'Answer') {
                throw new \InvalidArgumentException("Each acceptedAnswer must have @type set to 'Answer'.");
            }
            if (!isset($answer['text']) || !is_string($answer['text'])) {
                throw new \InvalidArgumentException("Each acceptedAnswer must have a string text.");
            }
        }
    }
}