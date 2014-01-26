<?php
class SearchController {
    private $gateway;
    private $num;
    private $filesPerPage;

	public function __construct(SearchGateway $gateway, $filesPerPage) {
		$this->gateway = $gateway;
    $this->filesPerPage = $filesPerPage;
	}

	public function find($queryString, $pageNum) {
      //тут мы можем при желании как-то обрабатывать $queryString
      // (отсекать окончания например), затем вызываем методы
      $files = $this->gateway->getResults($queryString, $pageNum, $this->filesPerPage);
      return $files;
	}

  public function countResults($string) {
    $rows = $this->gateway->getNumberOfRows($string);
    $this->num = $rows;
    return $rows;
  }

    private function inclineWord($number, $word1, $word2, $word5) {
    $lastTwoDigits = $number % 100;
    $lastDigit     = $lastTwoDigits % 10;

    if ($lastTwoDigits >= 10 && $lastTwoDigits <= 20) {
        return $word5;
    } elseif ($lastDigit == 1) {
        return $word1;
    } elseif ($lastDigit > 1 && $lastDigit < 5) {
        return $word2;
    } else {
        return $word5;
    }
    }
  
  public function countPages() {
    $pages = ceil($this->num/$this->filesPerPage);
    return $pages;
  }

	public function getHeaderString($queryString) {
       $string = "По запросу '<span class='search-query'>{$queryString}</span>' ";
       if ($this->num == 0) {
       	$string .= "совпадений не найдено.";
       } else {
       	$word = $this->inclineWord($this->num, "cовпадение", "совпадения", "совпадений");
       	$string .= "найдено {$this->num} {$word}:";
       }
      return $string;
	}

}