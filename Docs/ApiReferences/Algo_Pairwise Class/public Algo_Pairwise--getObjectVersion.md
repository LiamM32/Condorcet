## public Algo\Pairwise::getObjectVersion

> [Read it at the source](https://github.com/julien-boudry/Condorcet/blob/master/src/CondorcetVersion.php#L26)

### Description    

```php
public Algo\Pairwise->getObjectVersion ( [bool $major = false] ): string
```

Get the Condorcet PHP version who built this Election object. Usefull pour serializing Election.
    

#### **major:** *`bool`*   
true will return : '2.0' and false will return : '2.0.0'.    


### Return value:   

*(`string`)* Condorcet PHP version.


---------------------------------------

### Related method(s)      

* [static Condorcet::getVersion](/Docs/ApiReferences/Condorcet%20Class/public%20static%20Condorcet--getVersion.md)    
