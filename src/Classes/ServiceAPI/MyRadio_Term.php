<?php

namespace MyRadio\ServiceAPI;
use MyRadio\MyRadio\CoreUtils;
use MyRadio\MyRadio\MyRadioForm;
use MyRadio\MyRadio\MyRadioFormField;
use MyRadio\MyRadioException;

/**
 * 
 * The Term class represents broadcasting periods
 * Note: this is different to Semesters, those are teaching
 * periods related to modules and exams, terms here are
 * teaching periods related to when breaks are
 * 
 */
class MyRadio_Term extends ServiceAPI
{
    private int $term_id;
    private string $descr;
    private int $num_weeks;
    private $week_names;
    private $start_date;

    public function __construct($term_id) {
        $this->term_id = (int) $term_id;

	self::initDB();
        $result = self::$db->fetchOne(
            "SELECT * FROM public.terms WHERE termid=$1;",
            [$this->term_id]
        );

        if (empty($result)) {
            throw new MyRadioException("The specified term " . $this->term_id . " doesn't exist" . json_encode($result));
            return;
        }

        $this->start_date = $result['start'];
        $this->descr = $result['descr'] . date(" Y", strtotime($this->start_date));
        $this->num_weeks = (int) $result['weeks'];
        $this->week_names = json_decode($result['week_names']);
        if ($this->week_names == null) {
            $this->week_names = [];
        }
        
    }

    /**
     * Create a new term.
     *
     * @param int    $start The term start date
     * @param string $descr Term description e.g. Autumn 2036
     *
     * @return int The new termid
     */
    public static function addTerm($start, $descr, $num_weeks)
    {
        if (date('D', $start) !== 'Mon') {
            throw new MyRadioException('Terms must start on a Monday.', 400);
        }
        if (!is_numeric($num_weeks)){
            throw new MyRadioException('Weeks must be an integer.', 400); 
        }
        $num_weeks = (int)$num_weeks;
        // Let's make this a GMT thing, exactly midnight
        $ts = gmdate('Y-m-d 00:00:00+00', $start);
        $end = $start + (86400 * ((7*$num_weeks)-2)); // to Friday of the final week
        $te = gmdate('Y-m-d 00:00:00+00', $end);
        
        return self::$db->fetchColumn(
            'INSERT INTO terms (start, finish, descr, weeks) VALUES ($1, $2, $3, $4) RETURNING termid',
            [$ts, $te, $descr, $num_weeks]
        )[0];
    }

    /**
     * Returns a list of terms in the present or future.
     *
     * @param bool $currentOnly If only the present term should be output (if term time)
     * @return Array[Array] an array of arrays of terms
     * @TODO caching
     */ 
    public static function getAllTerms($currentOnly = false)
    {
        if ($currentOnly == "false") {
            $currentOnly = false;
        }

        $query = 'SELECT termid, EXTRACT(EPOCH FROM start) AS start FROM terms ';
        $query .= $currentOnly ? 'WHERE start <= now() AND finish > now() ' : '';
        $query .= 'ORDER BY start ASC';
        $result = self::$db->fetchAll($query);

        $terms = [];
        foreach ($result as $row) {
            $term = new self($row['termid']);
            $terms[] = $term;
        }

        return $terms;
    }

    /**
     * Returns if we are currently in term time.
     *
     * @return Boolean
     */
    public static function isTerm()
    {
    	return true;
        return (!empty(self::getAllTerms(true)));
    }

    public function getID() {
        return $this->term_id;
    }

    public function getTermDescr() {
        return $this->descr;
    }

    public function getTermWeeks() {
        return $this->num_weeks;
    }

    /**
	Return an array of week names in a term
	Stored as a JSON array in the DB.

	i.e. ['Week 0 Sem 1', 'Week 1 Sem 1'] etc

	@return array of strings, the week names
    }
    */
    public function getTermWeekNames() {
        return $this->week_names;
    }

    public function getTermStartDate() {
        return strtotime('Midnight '.gmdate('d-m-Y', strtotime($this->start_date)).' GMT');
    }

    /**
     * Returns the Term currently available for Season applications.
     * Users can only apply to the current term, or 28 days before the next one
     * starts.
     */
    public static function getActiveApplicationTerm()
    {
        $return = self::$db->fetchColumn(
            'SELECT termid FROM terms
            WHERE start <= $1 AND finish >= NOW() LIMIT 1',
            [CoreUtils::getTimestamp(strtotime('+28 Days'))]
        );

        if (empty($return)) {
            return;
        }

        return new self($return[0]);
    }

    public static function getTermForm()
    {
        return (
            new MyRadioForm(
                'sched_term',
                'Scheduler',
                'editTerm',
                [
                    'title' => 'Scheduler',
                    'subtitle' => 'Create Term',
                ]
            )
        )->addField(
            new MyRadioFormField(
                'descr',
                MyRadioFormField::TYPE_TEXT,
                [
                    'explanation' => 'Name the term. A value of "Autumn" denotes that this '
                                     . 'term represents the start of a new membership year.',
                    'label' => 'Term description',
                    'options' => ['maxlength' => 10],
                ]
            )
            )->addField(
                new MyRadioFormField(
                    'numweeks',
                    MyRadioFormField::TYPE_NUMBER,
                    [
                        'explanation' => 'How many weeks will there be in the term?',
                        'label' => 'Term weeks',
                    ]
                )
        )->addField(
            new MyRadioFormField(
                'start',
                MyRadioFormField::TYPE_DATE,
                [
                    'explanation' => 'Select a term start date. This must be a Monday.',
                    'label' => 'Start date',
                ]
            )
        );
        
    }

	public function toDataSource($mixins = []) {
		return [
			"term_id" => $this->getID(),
			"start" => $this->getTermStartDate(),
            "descr" => $this->getTermDescr(),
			"num_weeks" => $this->getTermWeeks(),
			"week_names" => $this->getTermWeekNames()
		];
	}
}
