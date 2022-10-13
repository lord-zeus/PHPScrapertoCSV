<?php

/**
 *
 * Class For Crawling the Websites
 */
class Scraping {
    public string $base_url;
    public function __construct(){
        $this->base_url = "https://www.coursera.org";
    }

    public function getCategoryCourses($category_name){
        $finder = $this->createDom($this->base_url . '/browse/'. $category_name);
        $classname = 'rc-BrowseProductCard';
        $nodes = $finder->query("//*[contains(@class, '$classname')]");

        $csv_values = array();
        foreach ($nodes as $k => $node) {
            $link_val = $node->getElementsByTagName('a');
            $link = $link_val[0]->getAttribute('href');
            $value = $this->grabCourseDetails($link, $category_name);
            array_push($csv_values, $value);
           if($k == 4) {
               break;
           }
        }
        var_dump( $csv_values);
        return $csv_values;
    }

    public function createDom($link){
        $html = file_get_contents($link);
        $DOM = new DOMDocument();
        libxml_use_internal_errors(true);
        $DOM->loadHTML($html);
        return new DomXPath($DOM);
    }

    public function grabCourseDetails($link, $category_name){
        $finder = $this->createDom($this->base_url . $link);
        $header = $finder->query("//h1[1]/text()[1]");
        $course_name = $header[0]->nodeValue;
        $instructor = $finder->query("//h3[1]/text()[1]");
        $instructor_name = $instructor[0]->nodeValue;
        $course_description_one = $finder->query("//div[@class='content-inner']/p[1]/text()[1]");
        $course_description_two = $finder->query("//div[@class='content-inner']/p[2]/text()[1]");
        $first_content = $course_description_one[0]->nodeValue;
        if($course_description_two[0] == null) $second_content = '';
        else $second_content = $course_description_two[0]->nodeValue;
        $final_content = $first_content . $second_content;
        $enrolled = $finder->query("//div[@class='rc-ProductMetrics']/div/span/strong/span/text()");
        $enrolled_value = str_replace(',', '', $enrolled[0]->nodeValue);
        $rating = $finder->query("//div[@class='_wmgtrl9 m-r-1s color-white']/span/span/text()");
        if($rating[0] == null) $rating_value = '';
        else $rating_value = str_replace(' ratings', '', $rating[0]->nodeValue);
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

    public function generateCSV($category){
        $data = $this->scraping->getCategoryCourses($category);
        var_dump($data);
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="work.csv"');

        array_unshift($data, array('Category Name', 'Course Name', 'First Instructor Name', 'Course Description', '# of Students Enrolled', '# of Ratings'));

        $fp = fopen("work.csv", "w");
        foreach ($data as $line) {
            fputcsv($fp, $line, ',');
        }
        fclose($fp);
        return $fp;
    }
}

class ClientSide {
    public CSVGenerator $csvGenerator;
    public function __construct(CSVGenerator $csvGenerator){
        $this->csvGenerator = $csvGenerator;
    }

    public function webUser(){
        ?>
        <html>
        <body>
        <div align="center">
            <form action="" method="POST">
                <b>Category Name:</b><input type="text" name="category_name"><br>
                <input type="submit">
            </form>
        </div>
        </body>
        </html>
        <?php
    }

    public function submitForm(){
        if (empty($_POST["category_name"])) {
            $errMsg = "Error! You didn't enter the Category Name.";
            echo $errMsg;
            return $this->webUser();
        } else {
            $category_name = $_POST['category_name'];
        }
        return $this->csvGenerator->generateCSV($category_name);
    }
}

$client = new ClientSide(new CSVGenerator(new Scraping()));
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    return $client->submitForm();
}
else return $client->webUser();