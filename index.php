<?php

/**
 *
 * Data Class for details and Error
 */
class Result {
    public bool $error;
    public string $message;
    public $data;

    public function __construct($error, $data = NULL, $message = ""){
        $this->error = $error;
        $this->data = $data;
        $this->message = $message;
    }
}

/**
 *
 * Class For Crawling the Websites
 */
class Scraping {
    private string $base_url;
    public function __construct(){
        $this->base_url = "https://www.coursera.org";
    }

    /**
     * @param $category_name
     * @return Result
     */
    public function getCategoryCourses($category_name){
        $result = $this->createDom($this->base_url . '/browse/'. $category_name);
        if($result->error){
            return $result;
        }
        $finder = $result->data;
        $classname = 'rc-BrowseProductCard';
        $nodes = $finder->query("//*[contains(@class, '$classname')]");
        $csv_values = array();
        foreach ($nodes as $k => $node) {
            $link_val = $node->getElementsByTagName('a');
            $link = $link_val[0]->getAttribute('href');
            $value = $this->grabCourseDetails($link, $category_name);
            array_push($csv_values, $value);
        }
        $result->data = $csv_values;
        return $result;
    }

    /**
     * @param $link
     * @return Result
     */
    private function createDom($link){

        libxml_use_internal_errors(true);
        $html = @file_get_contents($link);

        if(!$html){
            $message = 'Sorry Category Not Found Please Try Some Other ex Business';
            return new Result(true, NULL, $message);
        }
        $DOM = new DOMDocument();
        $DOM->loadHTML($html);
        return new Result(false, new DomXPath($DOM));
    }

    /**
     * @param $link
     * @param $category_name
     * @return array
     */
    private function grabCourseDetails($link, $category_name){
        $data = $this->createDom($this->base_url . $link);
        $finder = $data->data;
        $header = $finder->query("//h1[1]/text()[1]");
        $course_name = @$header[0]->nodeValue;
        $instructor = $finder->query("//h3[1]/text()[1]");
        $instructor_name = $instructor[0]->nodeValue;
        $course_description_one = $finder->query("//div[@class='content-inner']/p[1]/text()[1]");
        $course_description_two = $finder->query("//div[@class='content-inner']/p[2]/text()[1]");
        $first_content = @$course_description_one[0]->nodeValue;
        $second_content = @$course_description_two[0]->nodeValue;
        $final_content = $first_content . $second_content;
        $enrolled = $finder->query("//div[@class='rc-ProductMetrics']/div/span/strong/span/text()");
        $enrolled_value = str_replace(',', '', @$enrolled[0]->nodeValue);
        $rating = $finder->query("//div[@class='_wmgtrl9 m-r-1s color-white']/span/span/text()");
        $rating_value = str_replace(' ratings', '', @$rating[0]->nodeValue);
        $rating_value = str_replace(',', '', $rating_value);
        return [$category_name, $course_name, $instructor_name,$final_content,$enrolled_value,$rating_value];
    }

}

/**
 * Class To Generate the CVS File
 *
 */
class CSVGenerator {
    public Scraping $scraping;
    public function __construct(Scraping $scraping){
        $this->scraping = $scraping;
    }

    /**
     * @param $category
     * @return Result
     */
    public function generateCSV($category){
        $result = $this->scraping->getCategoryCourses($category);

        if ($result->error) {
            return $result;
        }

        array_unshift($result->data, array('Category Name', 'Course Name', 'First Instructor Name', 'Course Description', '# of Students Enrolled', '# of Ratings'));

        $fp = fopen("{$category}.csv", "w");
        foreach ($result->data as $line) {
            fputcsv($fp, $line, ',');
        }
        fclose($fp);
        return $result;
    }
}

/**
 *
 * Class for user User interface
 */
class ClientSide {
    public CSVGenerator $csvGenerator;
    public function __construct(CSVGenerator $csvGenerator){
        $this->csvGenerator = $csvGenerator;
    }

    /**
     * @return void
     * User Interface
     */
    public function webUser(){
        ?>
        <html>
        <body>
        <div align="center" style="margin-bottom: 50px; margin-top: 45vh">
            <form action="" method="POST">
                <b>Category Name:</b><input type="text" name="category_name"><br>
                <input type="submit">
            </form>
        </div>
        <div style="position: absolute; left: 20; bottom: 10;">
            <a href="https://raw.githubusercontent.com/lord-zeus/PHPScrapertoCSV/master/index.php">Source Code</a>
        </div>
        </body>
        </html>
        <?php
    }

    /**
     * @return void
     * Form
     */
    public function submitForm(){
        if (empty($_POST["category_name"])) {
            $this->webUser();
            echo( "<div style='display:flex; color: red; justify-content: center'>Error! You didn't enter the Category Name.</div>");
        } else {
            $category_name = preg_replace("/\s+/", "-", trim(strtolower($_POST['category_name'])));
            $data = $this->csvGenerator->generateCSV($category_name);
            $this->webUser();
            if($data->error){
                echo( "<div style='display:flex; color: red; justify-content: center'>Error! {$data->message}.</div>");
            }
            else echo( "<div style='display:flex; justify-content: center'><a href='{$category_name}.csv'>Download CSV ({$category_name})</a></div>");

        }
    }
}

$client = new ClientSide(new CSVGenerator(new Scraping()));
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $client->submitForm();
}
else $client->webUser();