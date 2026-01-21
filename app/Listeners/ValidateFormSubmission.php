<?php

namespace App\Listeners;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Statamic\Events\FormSubmitting;

class ValidateFormSubmission
{
    /**
     * Minimum seconds between form load and submission
     */
    protected int $minSubmissionTime = 3;

    /**
     * Maximum submissions per IP per hour
     */
    protected int $maxSubmissionsPerHour = 5;

    /**
     * Handle the form submission event.
     */
    public function handle(FormSubmitting $event): void
    {
        $submission = $event->submission;
        $data = $submission->data();

        // Check time-based validation
        if (!$this->validateSubmissionTime($data)) {
            $this->rejectSubmission($event, 'Form submitted too quickly. Please wait a moment and try again.');
            return;
        }

        // Check JavaScript token
        if (!$this->validateJsToken($data)) {
            $this->rejectSubmission($event, 'JavaScript validation failed. Please enable JavaScript and try again.');
            return;
        }

        // Check rate limiting
        if (!$this->validateRateLimit()) {
            $this->rejectSubmission($event, 'Too many submissions. Please try again later.');
            return;
        }

        // Remove spam prevention fields from submission data
        $this->cleanSubmissionData($submission);
    }

    /**
     * Validate that enough time has passed since the form was loaded.
     */
    protected function validateSubmissionTime(array $data): bool
    {
        if (!isset($data['_form_loaded'])) {
            Log::warning('Form spam prevention: Missing _form_loaded timestamp');
            return false;
        }

        $loadedAt = (int) $data['_form_loaded'];
        $now = time();
        $elapsed = $now - $loadedAt;

        if ($elapsed < $this->minSubmissionTime) {
            Log::info('Form spam prevention: Submission too fast', [
                'elapsed_seconds' => $elapsed,
                'minimum_required' => $this->minSubmissionTime,
            ]);
            return false;
        }

        return true;
    }

    /**
     * Validate the JavaScript token.
     */
    protected function validateJsToken(array $data): bool
    {
        if (!isset($data['_js_token']) || empty($data['_js_token'])) {
            Log::info('Form spam prevention: Missing JS token');
            return false;
        }

        $token = $data['_js_token'];

        if (!str_starts_with($token, 'human_')) {
            Log::info('Form spam prevention: Invalid JS token format');
            return false;
        }

        return true;
    }

    /**
     * Validate rate limiting by IP address.
     */
    protected function validateRateLimit(): bool
    {
        $ip = request()->ip();
        $cacheKey = 'form_submissions_' . md5($ip);

        $submissions = Cache::get($cacheKey, 0);

        if ($submissions >= $this->maxSubmissionsPerHour) {
            Log::warning('Form spam prevention: Rate limit exceeded', [
                'ip' => $ip,
                'submissions' => $submissions,
            ]);
            return false;
        }

        // Increment submission count with 1 hour expiry
        Cache::put($cacheKey, $submissions + 1, now()->addHour());

        return true;
    }

    /**
     * Reject the form submission with an error message.
     */
    protected function rejectSubmission(FormSubmitting $event, string $message): void
    {
        throw new \Illuminate\Validation\ValidationException(
            validator([], []),
            response()->json(['error' => $message], 422)
        );
    }

    /**
     * Remove spam prevention fields from the submission data.
     */
    protected function cleanSubmissionData($submission): void
    {
        $data = $submission->data();

        unset($data['_form_loaded']);
        unset($data['_js_token']);

        $submission->data($data);
    }
}
