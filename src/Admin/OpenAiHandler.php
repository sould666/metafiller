<?php

namespace Metafiller\Admin;

use OpenAI;

class OpenAiHandler {
    private $client;

    public function __construct() {
        $api_key = get_option('metafiller_openai_api_key');
        if (empty($api_key)) {
            // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Exception messages are not directly output.
            throw new \Exception(__('OpenAI API key is not set. Please configure it in the settings.', 'metafiller'));

        }

        // Create an OpenAI client using the correct initialization method
        $this->client = OpenAI::client($api_key);
    }

    /**
     * Generate a meta title using OpenAI.
     *
     * @param string $title Post or term title.
     * @param string $content Post or term content.
     * @return string SEO-friendly meta title (max 60 characters).
     * @throws \Exception If the OpenAI API call fails.
     */
    public function generateMetaTitle($title, $content) {
        $language = $this->getLanguage(); // Fetch the user-selected language
        $messages = [
            ['role' => 'system', 'content' => "You are an assistant specialized in SEO meta title generation. Generate outputs in $language."],
            ['role' => 'user', 'content' => "Generate a concise, compelling meta title for the following:\n\nTitle: $title\nContent: $content\n\nIt must be no longer than 60 characters."],
        ];

        try {
            $response = $this->client->chat()->create([
                'model' => 'gpt-3.5-turbo',
                'messages' => $messages,
                'max_tokens' => 60,
                'temperature' => 0.7,
            ]);

            // Log the raw response
//            error_log('Meta Title Response: ' . print_r($response, true));

            $meta_title = trim($response['choices'][0]['message']['content']);
            if (mb_strlen($meta_title) > 60) {
                $meta_title = mb_substr($meta_title, 0, 60); // Truncate if necessary
            }

            // Log the generated meta title
//            error_log("Generated Meta Title: $meta_title");

            return $this->ensureSentenceCompletion($meta_title);
        } catch (\Exception $e) {
//            error_log('Error generating meta title: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Generate a meta description using OpenAI.
     *
     * @param string $title Post or term title.
     * @param string $content Post or term content.
     * @return string SEO-friendly meta description (max 160 characters).
     * @throws \Exception If the OpenAI API call fails.
     */
    public function generateMetaDescription($title, $content) {
        $language = $this->getLanguage(); // Fetch the user-selected language
        $messages = [
            ['role' => 'system', 'content' => "You are an assistant specialized in SEO meta description generation. Generate outputs in $language."],
            ['role' => 'user', 'content' => "Generate a concise, engaging meta description for the following:\n\nTitle: $title\nContent: $content\n\nIt must be no longer than 160 characters."],
        ];

        try {
            $response = $this->client->chat()->create([
                'model' => 'gpt-3.5-turbo',
                'messages' => $messages,
                'max_tokens' => 160,
                'temperature' => 0.7,
            ]);

            // Log the raw response
//            error_log('Meta Description Response: ' . print_r($response, true));

            $meta_description = trim($response['choices'][0]['message']['content']);
            if (mb_strlen($meta_description) > 160) {
                $meta_description = mb_substr($meta_description, 0, 160); // Truncate if necessary
            }

            // Log the generated meta description
//            error_log("Generated Meta Description: $meta_description");

            return $this->ensureSentenceCompletion($meta_description);
        } catch (\Exception $e) {
//            error_log('Error generating meta description: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Ensure the text ends with a complete sentence.
     *
     * @param string $text The text to process.
     * @return string Text ending in a complete sentence.
     */
    private function ensureSentenceCompletion($text) {
        $text = trim($text, "\"'"); // Remove surrounding quotes
        $punctuation = ['.', '!', '?'];
        $last_char = mb_substr($text, -1);

        // If the last character is not valid punctuation, add a period
        if (!in_array($last_char, $punctuation, true)) {
            $text = rtrim($text, ',;') . '.';
        }

        return $text;
    }

    /**
     * Get the user-selected language for output generation.
     *
     * @return string The language to use ('English' or 'Polish').
     */
    private function getLanguage() {
        $language_code = get_option('metafiller_language', 'gb'); // Default to English (GB)
        return $language_code === 'pl' ? 'Polish' : 'English';
    }
}
