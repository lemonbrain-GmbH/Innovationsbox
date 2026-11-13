<?php

declare(strict_types=1);

namespace Devoda\BlockDeveloper\Legacy\Console;

use Devoda\BlockDeveloper\Legacy\Console\Helper\SymfonyQuestionHelper;
use Exception;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;

class Command extends \Symfony\Component\Console\Command\Command
{
    /**
     * @var InputInterface
     */
    protected $input;

    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * @var SymfonyQuestionHelper
     */
    protected $questionHelper;

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (method_exists($this, 'handle')) {
            $this->input = $input;
            $this->output = $output;
            $this->questionHelper = new SymfonyQuestionHelper();
            $this->handle();
            exit;
        }

        throw new Exception("The command is not correctly implemented (no 'handle' method found in this class)");
    }

    /**
     * @param      $question
     * @param null $default
     *
     * @return mixed
     */
    public function ask($question, $default = null)
    {
        return $this->questionHelper->ask($this->input, $this->output, new Question($question, $default));
    }

    /**
     * Prompt the user for input with auto completion.
     *
     * @param  string $question
     * @param  string $default
     *
     * @return string
     */
    public function anticipate($question, array $choices, $default = null)
    {
        return $this->askWithCompletion($question, $choices, $default);
    }

    /**
     * Prompt the user for input with auto completion.
     *
     * @param  string $question
     * @param  string $default
     *
     * @return string
     */
    public function askWithCompletion($question, array $choices, $default = null)
    {
        $question = new Question($question, $default);
        $question->setAutocompleterValues($choices);

        return $this->questionHelper->ask($this->input, $this->output, $question);
    }

    /**
     * @param       $question
     * @param array $choices
     * @param null  $default
     * @param null  $attempts
     *
     * @return mixed
     */
    public function choice($question, $choices = [], $default = null, $attempts = null)
    {
        $question = new ChoiceQuestion($question, $choices, $default);
        $question->setMaxAttempts($attempts);

        return $this->questionHelper->ask($this->input, $this->output, $question);
    }

    /**
     * Confirm the operation with the user.
     *
     * @param  string $question
     *
     * @return bool
     */
    public function confirm($question)
    {
        $question = '<comment>' . $question . ' [y/N]:</comment> ';
        $question = new ConfirmationQuestion($question, false);

        return $this->questionHelper->ask($this->input, $this->output, $question);
    }

    /**
     * @param      $key
     * @param null $default
     *
     * @return null
     */
    protected function getInputOption($key, $default = null)
    {
        $options = $this->input->getOptions();

        return isset($options[$key]) ? $options[$key] : $default;
    }

    /**
     * @return bool
     */
    protected function isInteractive()
    {
        return $this->input->isInteractive();
    }
}
