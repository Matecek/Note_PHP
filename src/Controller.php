<?php
declare(strict_types=1);

namespace App;

use App\Exception\ConfigurationException;
use App\Exception\StorageException;

require_once 'Database.php';
require_once 'View.php';
require_once 'Exception/ConfigurationException.php';

class Controller
{
    private const DEFAULT_ACTION = 'list';

    private static array $config= [];

    private Database $database;
    private array $request;
    private View $view;

    public static function initConfiguration(array $config): void
    {
        self::$config = $config;
    }

    /**
     * @throws ConfigurationException
     * @throws StorageException
     */
    public function __construct(array $request)
    {
        if (empty(self::$config['db'])) {
            throw new ConfigurationException('Config error');
        }
        $this->database = new Database(self::$config['db']);

        $this->request = $request;
        $this->view = new View();
    }

    public function run(): void
    {
        $viewpager = [];

        switch ($this->action()) {
            case 'create':
                $page = 'create';
                $created = false;
                $data = $this->getRequestPost();

                if (!empty($data)) {
                        $errors = [];

                        if (empty(trim($data['title']))) {
                            $errors[] = 'Tytuł nie może być pusty.';
                        }

                        if (empty(trim($data['description']))) {
                            $errors[] = 'Treść nie może być pusta.';
                        }

                        if (!empty($errors)) {
                            throw new StorageException(implode('<br>', $errors));
                        }

                        $created = true;
                        $this->database->createNote($data);
                        header('Location: /');
                }

                $viewpager['created'] = $created;
                break;
            case 'show':
                $viewpager = [
                    'title' => 'Moja notatka',
                    'description' => 'Opis',
                ];
                break;
            default:
                $page = 'list';
                $data = $this->getRequestGet();

                $viewpager['resultList'] = [
                    'title' => $data['title'] ?? '',
                    'description' => $data['description'] ?? '',
                ];
                break;
        }
        $this->view->render($page, $viewpager);
    }

    private function action(): string
    {
        $data = $this->getRequestGet();
        return $data['_action'] ?? self::DEFAULT_ACTION;
    }

    private function getRequestPost(): array
    {
        return $this->request['post'] ?? [];
    }

    private function getRequestGet(): array
    {
        return $this->request['get'] ?? [];
    }
}