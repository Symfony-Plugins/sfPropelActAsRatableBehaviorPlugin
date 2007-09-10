<?php



class sfRatingMapBuilder {

	
	const CLASS_NAME = 'plugins.sfPropelActAsRatableBehaviorPlugin.lib.model.map.sfRatingMapBuilder';

	
	private $dbMap;

	
	public function isBuilt()
	{
		return ($this->dbMap !== null);
	}

	
	public function getDatabaseMap()
	{
		return $this->dbMap;
	}

	
	public function doBuild()
	{
		$this->dbMap = Propel::getDatabaseMap('propel');

		$tMap = $this->dbMap->addTable('sf_ratings');
		$tMap->setPhpName('sfRating');

		$tMap->setUseIdGenerator(true);

		$tMap->addPrimaryKey('ID', 'Id', 'int', CreoleTypes::INTEGER, true, null);

		$tMap->addColumn('RATABLE_MODEL', 'RatableModel', 'string', CreoleTypes::VARCHAR, true, 255);

		$tMap->addColumn('RATABLE_ID', 'RatableId', 'int', CreoleTypes::INTEGER, true, null);

		$tMap->addColumn('USER_REFERENCE', 'UserReference', 'string', CreoleTypes::VARCHAR, false, 255);

		$tMap->addColumn('RATING', 'Rating', 'int', CreoleTypes::INTEGER, false, null);

	} 
} 