<?php


namespace Deployee\Plugins\DbTasks\Dispatcher;

use Deployee\Components\Persistence\LazyPDO;
use Deployee\Plugins\DbTasks\Definitions\SqlFileDefinition;
use Deployee\Plugins\Deploy\Definitions\Tasks\TaskDefinitionInterface;
use Deployee\Plugins\Deploy\Dispatcher\AbstractTaskDefinitionDispatcher;
use Deployee\Plugins\Deploy\Dispatcher\DispatchResult;
use Deployee\Plugins\Deploy\Dispatcher\DispatchResultInterface;

class SqlFileDispatcher extends AbstractTaskDefinitionDispatcher
{
    /**
     * LazyPDO
     */
    private $lazyPdo;

    /**
     * @param LazyPDO $lazyPdo
     */
    public function __construct(
        LazyPDO $lazyPdo
    ) {
        $this->lazyPdo = $lazyPdo;
    }

    /**
     * @param TaskDefinitionInterface $taskDefinition
     * @return bool
     */
    public function canDispatchTaskDefinition(TaskDefinitionInterface $taskDefinition): bool
    {
        return $taskDefinition instanceof SqlFileDefinition;
    }

    /**
     * @param TaskDefinitionInterface $taskDefinition
     * @return DispatchResultInterface
     * @throws \Deployee\Plugins\Deploy\Exception\DispatcherException
     */
    public function dispatch(TaskDefinitionInterface $taskDefinition): DispatchResultInterface
    {
        $definition = $taskDefinition->define();

        $errorMsg = '';
        $success = false;
        try {
            $statement = $this->lazyPdo->prepare(file_get_contents($definition->get('source')));
            $statement->execute();
            $success = true;
        } catch (\Error | \Exception $e)
        {
            $errorMsg = $e->getMessage();
        }

        return $this->getReturnDispatchResult($success, $definition->get('source'), $errorMsg);
    }

    /**
     * @param bool $success
     * @param string $file
     * @return DispatchResult
     */
    private function getReturnDispatchResult(bool $success, string $file, string $errorMsg = '')
    {
        $exitCode = $success ? 0 : 255;
        $message = $success ? 'Query file executed %s' : 'Query file could not be executed: %s';

        return new DispatchResult($exitCode, sprintf($message, $file), $errorMsg);
    }
}