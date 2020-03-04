<?php

/* ===КОд не разбивал по файлам с названиями классов для целостности повествования и удобства чтения в рамках ТЗ ======
 *
 * Здравствуйте, приступая к заданию я немного обдумал где мог бы использоваться этот модуль, как , и какие могли бы быть
 * расширения.
 *
 * В решении буду опираться на то, что мы хотим узнать данные по коду валюты, например USD / RUB, возможно не обязательно
 * указывать конкретную валюту или набор, но так как реализовывать внутренную логику формирования ответа не нужно, давайте
 * представим что данное решение может работать с одним ключем, можно будет расширить теоретически для выборки набора ключей
 * или брать всю информацию.
 *
 * 1 - по логике, отсутствовало поведение, которые бы описывало действия ,если бы мы не смогли получить данные даже с http
 * что делать точно не могу сказать, возвращать ошибку или использовать резервный источник данных из вне.
 * 2 - в каком виде будет представлен кэш я не знаю, возможно нужно сделать проверку на доступ, и существует ли сам кэш
 * 3 - с http источником, мы может так же получить разные виды ответов, не просто готовые данные, но и ошибки запросов
 *
 * Решение следующее, создадим абстрактный класс для наследования, от него будут создаваться наши источники данных.
 * Далее ,возможно излишне, есть два примера, проще и углубленней, оба - создание паттерна  «Цепочки ответственности»
 * О мыслях как бы добавить фасад ниже.
 * */

/**
 * Class CurrencyCheckerAbstract
 */
abstract class CurrencyCheckerAbstract // абстрактный класс нашего модуля
{
    /**
     * код валюты, на данный момент в данное реализации это будет один код
     * @var
     */
    protected $currencyCode;
    /**
     *  Следующий в цепочке событий
     * @var
     */
    protected $next;
    /**
     * Значение(я) полученые в результате выполнения
     * @var
     */
    protected $value;

    /**
     * @param CurrencyCheckerAbstract $source
     * Реазизуем сеттер параметра  next , используем в обоих вариантах
     */
    public function setNext(CurrencyCheckerAbstract $source): void
    {
        $this->next = $source;
    }

    /**
     * @return mixed
     * геттер для параметра next , использую только в варианте со строителем
     */
    public function getNext()
    {
        return $this->next;
    }

    /**
     * фунция возвращающения курса валюты , основная
     * @return float|string
     */
    public function findCurrency(): float
    {
        $result = $this->currencyValueExist(); // наличие, которое вернет данные

        if ($result) { //Если наш базовый поставщик данных может вернуть данные , получим их

            $this->value = $result;//Присвоим данные для дальнеших манипуляций

            $this->updateValue(); // А это функция обновления данных, будет реализована в каждом источнике своя, либо по обратной цепи

            $this->printResult();// что бы посмотреть визуально, выведем результат на экран

            // вернем результаты работы
            return $result;

        } elseif ($this->next) { //Если нет, передаем задачу по цепочке

            $this->next->findCurrency();

        } else //Цепочка окончена, тут можно выбросить exception,или дописать обработчик события
            echo 'Валюта (' . $this->currencyCode . ') не найдена или источник не дал результат';

        // но результат вернем в ожидаемом типе данных, опять же , смотря по ситуации, возможно вернуть 0 будет критично
        return 0;

    }

    /**
     *  Это сеттер кода валюты , только для строителя
     * @param $code
     */
    public function setCurrencyCode($code): void
    {
        $this->currencyCode = $code;
    }

    /**
     * @throws Exception
     * дебаг для себя, что бы визуально смотреть
     */
    public function printResult(): void
    {

        echo 'Источник ' . get_class($this) . ' нашел значение валюты ' . $this->currencyCode . ' стоимостью ' . $this->value . '<br>';
    }


    /**
     * @return float
     * необходимый к реализации метод получения данных
     */
    abstract public function currencyValueExist(): float; //

    /**
     * необходимый к реализации метод обновления данных,вернет успех в случае обновления
     * @return bool
     */
    abstract public function updateValue();//: bool; //
}


/*
 *
 * Здесь три типа источников данных
 *
 * */

/**
 * =======КЭШ========
 * Class CurrencyCacheChecker
 */
class CurrencyCacheChecker extends CurrencyCheckerAbstract
{

    /**
     * @return float
     */
    public function currencyValueExist(): float
    {
        // return false;
        return 55.55;
    }

    /**
     * @return bool
     */
    public function updateValue()//: bool
    {
    }
}

/**
 *  * =======БД========
 * Class CurrencyDbChecker
 */
class CurrencyDbChecker extends CurrencyCheckerAbstract
{

    /**
     * @return float
     * @throws Exception
     */
    public function currencyValueExist(): float
    {
        return false;
        //return random_int(11, 22);
    }

    /**
     * @return bool
     */
    public function updateValue()//: bool
    {
    }

}

/**
 *  * =======API/HTTP========
 * Class CurrencyApiChecker
 */
class CurrencyApiChecker extends CurrencyCheckerAbstract
{

    /**
     * @return float
     * @throws Exception
     */
    public function currencyValueExist(): float
    {
        // return false;
        return random_int(33, 44);
    }

    /**
     * @return bool
     */
    public function updateValue()//: bool
    {
    }

}

/**
 *  Вторая часть задания, что бы сделать еще.
 *
 * Тут давайте построим строителя, возможно избыточно, или лучше было бы сделать на фасадах, но предложение следущее
 * Если у нас будет добавлен или убран +-1 источник данных, либо нам необходимо пропустить один из них, нам будет легче
 * строить цепь зависимостей, к тому же, в нереализованных функциях updateValue для классов, в которых теоретически мы
 * записывам алгоритмы обновления данных в обратном порядке ( если логика такова, что из последнего нашедшего мы в обратную
 * сторону должны сохранить данные в каждый предыдущий) мы можем  сформировать и использовать обратную цепь для обновлений
 *
 *  Конечно лучше всего было бы применить некий фасад и сделать функции в нем getCurrency - она строит заранее заданную
 *  нами цепь и возвращает значения + обновляет их автматически/вариативно
 *  Используя фасад в проекте, нам не нужно будет переписывать логику получения данных,строителя,цепи , если  нужно будет удалить
 *  из цепи источник кэш или БД, добавить резервный HTTP. Мы изменим лишь создаваемую цепь в строителе или прямо в функции getCurrency фасада
 *
 * Для фасада, так же можно было бы добавить возможность вызова обновления данных из http, как достоверного источника данных,
 * приоритетным перед всеми остальными
 * */
/*
 * Class CheckerCreator
 */

class CheckerBuilder
{

    /**
     * @var CurrencyCheckerAbstract
     */
    protected $checker; // данная переменняя будет отвечать за первый источник данных
    /**
     * @var
     */
    protected $obj; // а это буферная переменная для хранения обьектов
    /**
     * @var
     */
    protected $code; // код валюты на всю цепь


    /**
     * CheckerCreator constructor.
     * @param $code
     * @param CurrencyCheckerAbstract|null $obj
     */
    public function __construct($code, CurrencyCheckerAbstract $obj = null) // конструктор строителя, добавил вариативности в создании
    {

        $this->code = $code;

        if ($obj) {
            $this->checker = $obj;
            $this->checker->setCurrencyCode($code);
        }

    }

    /**
     * @param CurrencyCheckerAbstract $obj
     * @return CurrencyCheckerAbstract
     */
    public function next(CurrencyCheckerAbstract $obj): \CurrencyCheckerAbstract
    {
        // формирует цепь зависимостей, ставит атрибуты, из за вариативности создания первого источника, проверяет на наличие
        if (!$this->checker) {
            $this->checker = $obj;
            $this->checker->setCurrencyCode($this->code);
        } else {
            if (!$this->checker->getNext())
                $this->checker->setNext($obj);

            if ($this->obj)
                if (!$this->obj->getNext())
                    $this->obj->setNext($obj);

            $this->obj = $obj;

            $this->obj->setCurrencyCode($this->code);
        }

        return $this->obj ?? $this->checker;

    }

    /**
     * @return CurrencyCheckerAbstract|null
     */
    public function get() // получить начальный эллемент цепи, в контексте всей цепи
    {
        return $this->checker;
    }

}

// Вариант со строителем, который можно вставить в фасад в общую функцию получения значения, формируем цепь
$checher = new CheckerBuilder('RUB'); //  new CheckerBuilder('RUB',new CurrencyCacheChecker()); Два варинта сделать первый источик
$checher->next(new CurrencyCacheChecker());
$checher->next(new CurrencyDbChecker());
$checher->next(new CurrencyApiChecker());
$checher->get();
var_dump($checher->get()->findCurrency());
//Тут раз такое пошло, следовало бы проверять на созданный экземпляр источник, он не должен повторяться и ссылаться на себя, но потом...


// Вариант попроще, сформировали цепочку с кодом валюты, просим вернуть данные с первого поповшегося
/*$cache_checker = new CurrencyCacheChecker('ruRu');
$DB_Check = new CurrencyDbChecker('enEn');
$API_Check = new CurrencyApiChecker('deDe');

$cache_checker->setNext($DB_Check);
$DB_Check->setNext($API_Check);
 var_dump($cache_checker->findCurrency());*/

?>