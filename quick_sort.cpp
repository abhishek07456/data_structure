/* Q: Write a program to accept N numbers in an array, and then sort the array using Quick Sort.*/
#include<iostream>
using namespace std;
void swap(int* a, int* b)
{
    int t = *a;
    *a = *b;
    *b = t;
}
int partition(int arr[],int low,int high)
{
	int p=arr[high];
	int i=low-1;
	for (int j = low; j <= high- 1; j++)
    {
        if (arr[j] <= p)
        {
            i++;
            swap(&arr[i], &arr[j]);
        }
    }
    swap(&arr[i + 1], &arr[high]);
    return (i + 1);

}
void quicksort(int arr[],int low,int high)
{
  if(low<high)
  {
  	int p=partition(arr,low,high);
  	quicksort(arr,low,p-1);
  	quicksort(arr,p+1,high);
  }
}
void print(int arr[],int n)
{
	for(int i=0;i<n;i++)
	{
	   cout<<arr[i]<<" ";
	}
	cout<<endl;
}
int main()
{
	int size,ele;
	cout<<"Enter the size of array : ";
	cin>>size;
	int arr[size];
	cout<<"Enter elements ->"<<endl;
	for(int i=0;i<size;i++)
	   cin>>arr[i];
	quicksort(arr,0,size-1);
	cout<<"Sorted Array : ";
	print(arr,size);
	return 0;
}
