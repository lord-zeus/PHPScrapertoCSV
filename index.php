<?php
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
        foreach ($nodes as $node) {

//            var_dump($node);
            $link_val = $node->getElementsByTagName('a');
            $link = $link_val[0]->getAttribute('href');
            $value = $this->grabCourseDetails($link);
            array_push($csv_values, $value);

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

    public function grabCourseDetails($link){
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
        var_dump($rating_value);
        return [
            'course_name' => $course_name,
            'instructor_name' => $instructor_name,
            'description' => $final_content,
            'enrolled' => $enrolled_value,
            'rating' => $rating_value
        ];
    }

}

class CSVGenerator {
    public Scraping $scraping;
    public function __construct(Scraping $scraping){
        $this->scraping = $scraping;
    }

    public function generateCSV($data){

    }
}
//return new CSVGenerator(new Scraping('business'));
$courses = new Scraping();
return $courses->getCategoryCourses('business');


//$base_url = "https://www.coursera.org";
//
//$finder = createDom($base_url . '/browse/business');
//$classname = 'rc-BrowseProductCard';
//$nodes = $finder->query("//*[contains(@class, '$classname')]");
//
//$csv_values = array();
//foreach ($nodes as $node) {
//
//    var_dump($node->nodeValue);
//    $link_val = $node->getElementsByTagName('a');
//    $link = $link[0]->getAttribute('href');
//
//}
//return 'ok';
//$handle = fopen("C:\Users\Stephen\Documents\WorkCSV\work.csv", "w");
//if (false !== $handle) {
//    fputcsv($handle, $csv_values);
//}
//
//function createDom($link){
//    $html = file_get_contents($link);
//    $DOM = new DOMDocument();
//    libxml_use_internal_errors(true);
//    $DOM->loadHTML($html);
//    return new DomXPath($DOM);
//}
//
//function getCourseDetails($link){
//    createDom($b)
//}